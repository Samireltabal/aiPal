# Agent Workflows — Implementation Plan (v1)

**Status:** Planned
**Estimated effort:** 5–7 days
**Phase:** 14 (High Impact)

---

## Goal

Enable users to save a **scheduled prompt with a scoped toolset and a delivery destination**, triggerable four ways: cron schedule, manual button, inbound webhook, or incoming message prefix/regex.

The LLM (via existing `ChatAgent`) handles tool chaining, filtering, and summarization. We build **trigger dispatch**, not per-service integrations.

---

## Core Principles

1. **No per-service code.** We never write a Jira/GitHub/Gmail webhook handler. Users paste our generic webhook URL into external services themselves.
2. **Reuse `ChatAgent`.** A workflow is a prompt run through the existing agent with a filtered tool set. No DAG executor.
3. **Delivery via existing channels.** WhatsApp, Telegram, email, browser notification — all already built in Phases 7/10.
4. **One trigger per workflow.** Clone if you need multiple triggers for the same logic.

---

## Data Model

### Migration 1: `workflows`

```php
Schema::create('workflows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->boolean('enabled')->default(true);

    // What the workflow does
    $table->text('prompt');
    $table->json('enabled_tool_names'); // e.g. ['google_calendar', 'github_prs']
    $table->enum('delivery_channel', ['telegram', 'whatsapp', 'email', 'notification', 'none'])
        ->default('notification');

    // Trigger
    $table->enum('trigger_type', ['schedule', 'webhook', 'message', 'manual']);
    $table->string('cron_expression')->nullable();            // schedule only
    $table->string('webhook_token', 64)->nullable()->unique(); // webhook only
    $table->enum('message_channel', ['whatsapp', 'telegram', 'chat', 'any'])->nullable(); // message only
    $table->string('message_trigger_pattern')->nullable();    // message only (prefix or regex)

    $table->timestamp('last_run_at')->nullable();

    $table->timestamps();
    $table->index(['user_id', 'enabled']);
    $table->index(['trigger_type', 'enabled']);
});
```

### Migration 2: `workflow_runs`

```php
Schema::create('workflow_runs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
    $table->enum('status', ['pending', 'running', 'success', 'failed']);
    $table->longText('output')->nullable();
    $table->text('error')->nullable();
    $table->integer('duration_ms')->nullable();
    $table->json('trigger_payload')->nullable(); // body for webhook/message triggers
    $table->enum('triggered_by', ['schedule', 'webhook', 'message', 'manual']);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->timestamps();
    $table->index(['workflow_id', 'created_at']);
});
```

---

## New Files

| # | File | Purpose |
|---|------|---------|
| 1 | `database/migrations/XXXX_create_workflows_table.php` | Schema |
| 2 | `database/migrations/XXXX_create_workflow_runs_table.php` | Schema |
| 3 | `app/Models/Workflow.php` | Eloquent model, fillable, casts, `runs()` relation, `matchesMessage()` helper |
| 4 | `app/Models/WorkflowRun.php` | Eloquent model |
| 5 | `app/Services/WorkflowRunner.php` | Thin wrapper: given a `Workflow` + optional trigger payload, invoke `ChatAgent` with filtered tools, log `WorkflowRun` |
| 6 | `app/Jobs/RunWorkflowJob.php` | Queued job — dispatched by all four trigger paths, calls `WorkflowRunner` |
| 7 | `app/Console/Commands/DispatchDueWorkflows.php` | Finds workflows with `trigger_type='schedule'` + matching cron, dispatches jobs |
| 8 | `app/Http/Controllers/WorkflowWebhookController.php` | One route: `POST /webhooks/workflow/{token}` → dispatches job |
| 9 | `app/Services/WorkflowMessageMatcher.php` | Helper: given user + channel + text, returns first matching `Workflow` or null |
| 10 | `app/Livewire/Workflows.php` | List, create, edit, delete, "Run now", view run history |
| 11 | `resources/views/livewire/workflows.blade.php` | UI |
| 12 | `app/Ai/Tools/RunWorkflowTool.php` | Optional: lets AI trigger a workflow by name from chat ("run my morning brief") |

**Route additions:**
- `Route::post('webhooks/workflow/{token}', WorkflowWebhookController::class)->name('workflows.webhook');` (public, no auth — token IS the auth)
- `Route::get('workflows', Workflows::class)->middleware('auth')->name('workflows');`

**Scheduler addition (`bootstrap/app.php` or `routes/console.php`):**
```php
Schedule::command('workflows:dispatch-due')->everyMinute()->withoutOverlapping();
```

**Hook additions (minimal):**
- `app/Jobs/ProcessWhatsAppMessageJob.php` — before `ChatAgent::run()`, call `WorkflowMessageMatcher::match($user, 'whatsapp', $text)`. If match, dispatch `RunWorkflowJob` and return.
- `app/Jobs/ProcessTelegramMessageJob.php` — same for telegram.
- `app/Livewire/Chat.php` or wherever chat messages enter — same for chat channel.

---

## Execution Flow Per Trigger

### Schedule
```
artisan schedule:run (every minute)
  → workflows:dispatch-due
    → for each enabled schedule workflow:
      → Cron::fromString($cron)->isDue($now) ?
        → dispatch RunWorkflowJob($workflow, triggeredBy: 'schedule')
```

### Manual
```
User clicks "Run now" in Livewire UI
  → dispatch RunWorkflowJob($workflow, triggeredBy: 'manual')
  → flash success, link to run log
```

### Webhook
```
External service POSTs to /webhooks/workflow/{token}
  → WorkflowWebhookController finds workflow by token
  → 404 if not found or disabled
  → dispatch RunWorkflowJob($workflow, triggeredBy: 'webhook', payload: request body)
  → return 202 Accepted
```

### Message
```
WhatsApp/Telegram/chat message arrives
  → existing ProcessXxxJob → before ChatAgent:
    → WorkflowMessageMatcher::match($user, $channel, $text)
    → if match:
      → dispatch RunWorkflowJob($workflow, triggeredBy: 'message', payload: [text, channel, sender])
      → skip ChatAgent default handling
    → else:
      → continue to ChatAgent as usual
```

### All paths converge at `WorkflowRunner::run()`

```php
public function run(Workflow $workflow, string $triggeredBy, ?array $payload = null): WorkflowRun
{
    $run = WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => 'running',
        'trigger_payload' => $payload,
        'triggered_by' => $triggeredBy,
        'started_at' => now(),
    ]);

    $startedAt = microtime(true);
    try {
        $prompt = $this->buildPrompt($workflow, $payload);
        $tools = $this->resolveTools($workflow->user, $workflow->enabled_tool_names);

        $output = ChatAgent::for($workflow->user)
            ->withTools($tools)
            ->run($prompt);

        $this->deliver($workflow, $output);

        $run->update([
            'status' => 'success',
            'output' => $output,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'finished_at' => now(),
        ]);

        $workflow->update(['last_run_at' => now()]);
    } catch (\Throwable $e) {
        $run->update([
            'status' => 'failed',
            'error' => $e->getMessage(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'finished_at' => now(),
        ]);
        throw $e;
    }

    return $run;
}

private function buildPrompt(Workflow $workflow, ?array $payload): string
{
    $prompt = $workflow->prompt;
    if ($payload !== null) {
        $prompt .= "\n\nTrigger payload:\n" . json_encode($payload, JSON_PRETTY_PRINT);
    }
    return $prompt;
}
```

---

## UI (`/workflows`)

### List View
- Table: name, trigger summary (e.g., "every weekday 8am" / "webhook" / "WhatsApp: /morning"), last run status + time, enabled toggle, actions (Run now, Edit, Delete, View runs)
- "New workflow" button

### Create/Edit Form
- Name, description
- Trigger type (radio): Schedule / Manual / Webhook / Message
- Conditional fields based on trigger type:
  - Schedule → cron picker with presets ("every weekday 8am", "every hour", "every Monday", custom cron string)
  - Webhook → display read-only webhook URL + "Copy" button + "Regenerate token" button
  - Message → channel dropdown + pattern input + "Test match" helper
  - Manual → no fields
- Prompt textarea (supports `{{trigger_payload}}` hint for webhook/message types)
- Tools — checkbox grid grouped by category (pulled from `ToolRegistry`)
- Delivery channel dropdown
- Enabled toggle
- Save button

### Run History View
- Per-workflow detail page
- Last 50 runs as a list: status icon, started_at, duration, triggered_by badge
- Click a row → expands to show full output, error, payload
- "Re-run this" button on failed runs

---

## Security

| Surface | Control |
|---------|---------|
| Webhook endpoint | Token in URL is 64-char random UUID, unique per workflow. Treat as a bearer token. No auth middleware (intentional — external services can't send our session cookies). Rate-limit per token (throttle middleware). |
| Webhook payload | Stored verbatim in `trigger_payload` — log truncation if >100KB. Never eval, never pass as shell args. |
| Message trigger regex | Validate pattern compiles on save (try-catch `preg_match`). Cap pattern length at 255 chars. |
| Tool access | `enabled_tool_names` is a whitelist — `ChatAgent` invocation only exposes those. |
| User isolation | Every query scoped by `user_id`. Webhook controller loads workflow by token then sets `Auth::setUser($workflow->user)` for the job. |
| Prompt injection via webhook payload | Accepted risk — user authored the prompt, they know what comes in. Document the risk in UI. |

---

## Testing Plan

### Feature tests (new `tests/Feature/Workflows/`)
1. `test_user_can_create_workflow`
2. `test_user_cannot_see_other_users_workflows`
3. `test_schedule_workflow_dispatches_job_when_cron_matches`
4. `test_schedule_workflow_skipped_when_cron_does_not_match`
5. `test_schedule_workflow_skipped_when_disabled`
6. `test_webhook_endpoint_dispatches_job_with_payload`
7. `test_webhook_endpoint_404s_for_invalid_token`
8. `test_webhook_endpoint_404s_for_disabled_workflow`
9. `test_message_matcher_finds_prefix_match`
10. `test_message_matcher_finds_regex_match`
11. `test_message_matcher_returns_null_for_non_match`
12. `test_message_matcher_respects_channel_filter`
13. `test_workflow_runner_logs_success_run`
14. `test_workflow_runner_logs_failed_run_with_error`
15. `test_workflow_runner_filters_tools_to_enabled_set`
16. `test_manual_run_button_dispatches_job`
17. `test_invalid_cron_expression_rejected_on_save`
18. `test_invalid_regex_pattern_rejected_on_save`

### Unit tests
- `WorkflowMessageMatcher` with table-driven cases
- Cron matching edge cases (timezone, DST)

---

## Build Phases (order matters)

### Phase A — Backend core (days 1–2)
1. Migrations
2. Models (`Workflow`, `WorkflowRun`) with relations and casts
3. `WorkflowRunner` service + `RunWorkflowJob`
4. `DispatchDueWorkflows` command + schedule registration
5. Tests for runner, scheduler, models

### Phase B — Triggers (days 3–4)
6. `WorkflowWebhookController` + route + throttling
7. `WorkflowMessageMatcher` + hook into existing message jobs
8. Tests for webhook + message triggers

### Phase C — UI (days 4–5)
9. `Workflows` Livewire component — list, create, edit, delete, run now
10. Blade view with conditional trigger fields, cron picker presets, copy-URL UX
11. Run history view

### Phase D — Polish (days 5–6)
12. `RunWorkflowTool` — let AI trigger workflows by name from chat
13. Sidebar nav entry for Workflows page
14. README section + one example workflow in docs
15. Final test pass, Pint, manual smoke test

### Phase E — Ship (day 6–7)
16. Feature flag the page link if needed
17. PR with all tests passing
18. Merge + update `project_status.md`

---

## Out of Scope (v2 candidates)

- **Per-service integrations** (native Jira webhook signature verify, Gmail push subscriptions, GitHub signed webhooks) — add only when we feel the pain
- **Multi-trigger workflows** — clone for now
- **Event filter DSL at trigger level** — the prompt handles filtering
- **Semantic message routing** ("any urgent-sounding message") — that's a classifier
- **Visual DAG builder** — the prompt IS the DAG
- **Workflow versioning / history of edits** — edit = new version could come later
- **Shared / team workflows** — single-user only in v1
- **Nested workflows** (workflow A triggers workflow B) — possible via `RunWorkflowTool` but not a featured path

---

## Risk Register

| Risk | Likelihood | Mitigation |
|------|-----------|------------|
| Cron library drift (timezone bugs) | Low | Use `dragonmantank/cron-expression` (already a Laravel dep) |
| Webhook endpoint becomes DDoS target | Medium | `throttle:60,1` per token + Cloudflare at edge |
| Long-running workflow blocks queue worker | Medium | Queue on separate `workflows` connection or high-timeout worker; add 5-min hard timeout |
| LLM tool hallucination produces wrong output | Medium | Run history shows output; user can refine prompt; manual re-run button |
| Message matcher catches messages user didn't intend as triggers | Low | Prefix is the default; regex requires explicit opt-in; "Test match" UI helper |
| Prompt injection from webhook payload | Low | Document for user; `trigger_payload` is clearly scoped in the prompt template |

---

## Success Criteria

1. User can create a scheduled workflow (e.g., morning brief) and it runs on time with correct delivery.
2. User can paste the generated webhook URL into Jira and new-issue events trigger a WhatsApp message.
3. User can text `/morning` to WhatsApp bot and get a workflow response instead of default chat behavior.
4. Run history shows success/failure with full output for debugging.
5. All 18+ tests pass, Pint clean, no new linter warnings.

---

## Decisions Log

| Decision | Reason |
|----------|--------|
| Reuse `ChatAgent` instead of custom executor | Avoids duplicating tool orchestration; LLM already does step chaining well |
| One trigger per workflow | Simpler schema + UI; clone if multiple triggers needed |
| Generic webhook URL (not per-service) | Zero code maintenance for external service API changes; user pastes URL once |
| Prefix/regex message matching (not semantic) | Deterministic, fast, debuggable; semantic routing deferred to v2 |
| No workflow versioning in v1 | Edits overwrite; run history preserves output so old behavior is traceable |
| `trigger_payload` stored as JSON | Debugging; also available in prompt via template interpolation |
