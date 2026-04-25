# Sprint Plan — Contexts + Connections + Context-Scoped Forward-to-aiPal

**Sprint estimate:** ~9 days (revised after advisor review)
**Status 2026-04-24:** Days 1–8 shipped in one session. **Day 5 (stop settings-UI dual-writes) + Day 9 (scalar-column drop) bundled as a follow-up.** Suite: 268 tests (+34 in this sprint), all green on 512MB memory. Scalar columns still populated via backfill + preserved for rollback safety.

## Pick-up checklist (next session)

Entry point for resuming this work:

1. **User verifies in dev:**
   - Run `php artisan migrate` (applies 4 new migrations including backfill)
   - Visit `/settings/contexts` — Personal default context should appear
   - Create "Acme Corp" (work) + "Project Phoenix" (freelance) via UI
   - Move a connection between contexts; confirm badge color updates
   - Enable inbound email in Settings → Forward-to-aiPal, then forward a test email
2. **Day 5/9 work remaining:**
   - [ ] Inference-rules UI (currently editable only via tinker) — a small list editor on each context card
   - [ ] Rewrite settings UI write paths to target `connections` instead of scalar columns on `users`
   - [ ] Rewrite `User::hasTelegramLinked()` / `hasWhatsAppLinked()` / `hasInboundEmailEnabled()` / `hasJiraConnected()` / `hasGitLabConnected()` / `hasGitHubConnected()` to read from connections
   - [ ] Grep sweep for any remaining scalar-column reads (`telegram_chat_id`, `whatsapp_phone`, `inbound_email_token`, `jira_*`, `gitlab_*`, `github_token`)
   - [ ] Drop-columns migration: `users.telegram_chat_id`, `telegram_conversation_id`, `whatsapp_phone`, `whatsapp_conversation_id`, `jira_host/email/token`, `gitlab_host/token`, `github_token`, `inbound_email_token`
   - [ ] Remove scalar fields from `User::$fillable` attribute
   - [ ] Test-update sweep (~20 tests)
3. **Then decide:** Microsoft Graph (phase 3) vs other roadmap priorities.
**Decision locked 2026-04-24:** combined morning cast with context labels (per-context casts deferred to a later sprint)
**Scope:** Phase 1 (Contexts + Connections refactor) + Phase 4 (Forward-to-aiPal context routing)
**Out of scope this sprint:** Microsoft Graph integration, per-context morning casts, Teams, multi-account UI polish beyond the basics

---

## Day-by-day breakdown

Rough, not rigid. Each "day" is a commit-worthy unit of work.

### Day 1 — Schema + models, no refactor yet

- Migration: create `contexts` table (include `slug` column for forward-address suffix, derived from `name`, editable; and `inference_rules` JSON column)
- Migration: create `connections` table
- Migration: add `context_id` (nullable FK) to `tasks`, `reminders`, `notes`, `memories`, `agent_conversations`
- New models: `App\Models\Context`, `App\Models\Connection`
- Cast `connections.credentials` with Laravel `encrypted` cast (non-negotiable)
- **Factories with ergonomic helpers:** `User::factory()->withContext()->withGoogleConnection()` and similar for telegram/whatsapp/inbound. These are used by ~40 tests in Days 4–5, so invest up front.
- **Seed every existing user with a default `personal` Context before backfill runs.** Without this, Day 2 backfill hits FK errors. This step is load-bearing — do it inside the contexts-table migration or as the first step of Day 2's data migration.
- **Tests:** basic CRUD + encryption round-trip + factory helpers (4–5 unit tests)

### Day 2 — Backfill + seeding, still non-breaking

- Write a one-shot data migration `BackfillContextsAndConnectionsMigration`:
  - For every existing user, create a default `personal` `Context` (name: "Personal", is_default: true)
  - Migrate `users.google_token` → one `Connection` row (provider: google, capabilities: [mail, calendar], context: default)
  - Migrate `users.telegram_chat_id` + `users.whatsapp_phone` → Connection rows
  - Migrate `users.inbound_email_token` → a Connection row (provider: inbound_email, inbox_slug: "personal")
  - Migrate Jira, GitLab, GitHub tokens → Connection rows
- Backfill `context_id` on `tasks`/`reminders`/`notes`/`memories`/`agent_conversations` → each user's default context
- **Tests:** migration idempotency + verify row counts match per user

### Day 3 — User-facing accessors, read-only refactor

- Methods on `User`: `contexts()`, `defaultContext()`, `connections()`, `connectionsFor($capability, ?Context $context = null)`, `defaultConnectionFor($capability)`
- Methods on `Context`: `connections()`, `tasks()`, `reminders()`, `notes()`, `memories()`, `inboundEmailAddress()` (slug-suffixed)
- Methods on `Connection`: `context()`, `isCapableOf($capability)`
- **Keep the scalar columns on `users` for now** — they're the source of truth until Day 5. Accessors read from connections but call-sites still use scalars.
- **Tests:** accessor correctness (6-8 tests covering happy + empty paths)

### Day 4a — Refactor briefing + calendar read path

- `DailyBriefingJob`: swap to read from `connectionsFor('calendar')` and `Context::tasks()`. Combined cast collects per-context sections.
- `DailyBriefingAgent`: prompt now takes `$contextsContext` (array of context-labeled sections)
- `GoogleCalendarService::listTodayEvents`: accept a `Connection` (or `Context`) arg; old signature stays as a thin wrapper that uses default
- **Scalar columns still populated via dual-write** — don't drop anything yet.
- **Tests:** update `DailyBriefingJobTest`, `MorningCastChannelTest`, `BriefingSettingsTest`. Expect ~10 test updates.

### Day 4b — Refactor write paths (tasks/reminders/notes/memories) + inbound

- `ReminderParserAgent` / `CreateReminder` tool: attach `context_id` (default context for now; inference added Day 7)
- `ForwardedEmailProcessor`: `saveTask/saveReminder/saveMemory/saveNote` all set `context_id`
- Settings UI linking an integration writes to `connections` in addition to the existing scalar write (dual-write)
- **Tests:** `InboundEmailControllerTest`, `SettingsTest`, `WhatsAppVoiceRateLimitTest`, `ReminderNotificationTest`, `TelegramWebhookControllerTest`. Expect ~15 test updates.

### Day 5 — Finish read-path migration + stop dual-writes

- Any remaining read sites still using scalar columns (grep sweep): move to `connections`
- **Stop the dual-write**: settings UI writes only to `connections`. Scalar columns stay populated from Day 2's backfill but go stale from here on.
- End of Day 5: no active code path reads or writes the scalar columns; they exist only as a rollback safety net.
- **Tests:** any leftover suite updates (~10 tests).

### Day 6 — Contexts UI

- New Livewire component: `App\Livewire\Contexts` with route `/settings/contexts`
- Add new context: pick `kind` (Work/Freelance/Personal), name, color, slug
- Archive (soft) / unarchive
- Set default
- Move a connection to a different context (dropdown on each connection row)
- Color badges across the app (small component: `<x-context-badge :context="..." />`)
- **Tests:** create/archive/default-switch via Livewire tests (4-5 tests)

### Day 7 — Context inference for inbound email + slug-suffix routing

- `InboundEmailController`: parse `forward-{token}-{slug}@...` into token + slug. Empty slug → default context.
- If slug is present but doesn't match any of the user's contexts → 404 with clear message (reply email + 400 to Worker)
- **Inference precedence (explicit spec):**
  1. **Slug-suffix in to-address always wins** — if present, it's the user's explicit intent.
  2. **Sender-domain rules, first-match by user-defined order.** User drags to reorder rules per-context from the Contexts UI.
  3. **Mandatory catch-all**: default context (usually `personal`) is always the fallback. No email is ever unclassified — silent drops kill trust.
- **Log slug/inference mismatches.** If slug says Phoenix but sender-domain rules would have picked Work, record a warning — it's signal that the user's rules need tuning (useful for a future "your rules sent 3 Work emails to Phoenix this week" nudge).
- Settings UI: on a Work/Freelance context, show its forward address + a drag-orderable list of sender-domain rules. Rules editor is a simple list: `@acme.com`, `@acme-hr.com`, etc.
- **Tests:** 6–8 new tests in `InboundEmailControllerTest` covering slug routing, rule precedence, catch-all fallback, slug-vs-rule mismatch logging

### Day 8 — Combined morning cast with context labels + polish

- `DailyBriefingAgent` prompt update: if the user has ≥2 non-archived contexts with content today, bullets get context badges — e.g. `[Work] Ship Stripe refund flow before 11am`
- Chat tools (`usage_insights`, others): optional `context` arg. Wire it for `usage_insights` now; leave other tools for later.
- Buffer time for bugfixes surfaced during integration testing
- Scalar columns still present but fully unused by the app

### Day 9 — Scalar-column drop + final cleanup

- **Final migration drops the scalar columns**: `users.google_token`, `users.telegram_chat_id`, `users.whatsapp_phone`, `users.inbound_email_token`, `users.jira_host/email/token`, `users.gitlab_host/token`, `users.github_token`
- Remove scalar column references from `User::$fillable` (the big `#[Fillable(...)]` attribute)
- Remove any helper methods on `User` that wrapped the scalar columns (`hasTelegramLinked`, etc. — replace with `connections()->where('provider', 'telegram')->exists()`)
- Final full-suite run; ensure ≥80% coverage maintained
- Update `.env.example` comments if any referenced removed columns
- Why last: any bug uncovered in Days 6–8 could have been masked by a scalar-column read. Keeping them until everything is exercised end-to-end is the safety benefit the dual-write window was built for.

---

## Files touched / added (at a glance)

**New:**
- `database/migrations/2026_04_XX_create_contexts_table.php`
- `database/migrations/2026_04_XX_create_connections_table.php`
- `database/migrations/2026_04_XX_add_context_id_to_user_owned_tables.php`
- `database/migrations/2026_04_XX_backfill_contexts_and_connections.php`
- `database/migrations/2026_04_XX_drop_user_scalar_integration_columns.php`
- `app/Models/Context.php`
- `app/Models/Connection.php`
- `app/Livewire/Contexts.php` + `resources/views/livewire/contexts.blade.php`
- `resources/views/components/context-badge.blade.php`
- `tests/Feature/Context/ContextsUiTest.php`
- `tests/Feature/Context/ConnectionsMigrationTest.php`
- `tests/Feature/Inbound/InboundEmailContextRoutingTest.php`

**Modified:**
- `app/Models/User.php` — drop scalar `#[Fillable(...)]` entries, add `contexts()`/`connections()`/`defaultContext()`
- `app/Jobs/DailyBriefingJob.php` + `app/Ai/Agents/Briefing/DailyBriefingAgent.php`
- `app/Services/ForwardedEmailProcessor.php` — attach `context_id` on every save
- `app/Http/Controllers/Inbound/InboundEmailController.php` — slug parsing
- `app/Services/GoogleCalendarService.php`
- `app/Services/TelegramService.php`, `WhatsAppService.php` — accept connection
- `app/Notifications/DailyBriefingNotification.php` — `via()` reads from connections
- All 234 existing tests — most pass unchanged; ~20-30 will need updates where they touched scalar columns

---

## Test scope

- **New tests:** ~25 (models, migrations, UI, context routing, inference)
- **Updated tests:** ~40 (removing scalar-column assertions, replacing with connection setup via the new factory helpers). Grep-check of the current suite shows at least 40 tests that construct `User` with scalar integration columns. Invest in the factory helpers on Day 1 to minimize repeat boilerplate across these updates.
- **End-of-sprint target:** all 270+ tests green, coverage ≥80%

---

## Rollback plan

If Day 9's scalar-column drop goes sideways (foreign-key issues, missed call sites):
- The drop migration has a matching `down()` that re-adds the columns empty
- A reverse-backfill script (the mirror of Day 2) can repopulate scalars from `connections` if needed for emergency rollback
- Worst case: revert Day 9's commit. Because Day 5 already stopped writes to scalars, those values are stale-but-present — the app still runs entirely off `connections`, and a follow-up session can retry the drop once the missed call site is fixed.

Days 1–8 are individually revertable via git. Day 5 (stopping dual-writes) and Day 9 (dropping columns) are the two real inflection points where rollback requires explicit work.

---

## Known risks (flagged by advisor)

1. **Test debt is the longest part.** Many existing tests create users with `telegram_chat_id` inline — those all need to create a `Connection` row instead. Budget generously.
2. **Refresh-token lifecycle.** Not handled this sprint — goes in a follow-up. For now, connections with expired tokens surface as errors at use time (same behavior as today's scalars).
3. **Context inference edge cases.** A work email forwarded from a personal account breaks sender-domain inference. Solution: explicit slug-suffix is always the override. Document this; don't try to be clever.
4. **User's personal data is the test bed.** After this sprint lands, the user should be able to set up their Full-time + Freelance(s) + Personal contexts end-to-end on inbound email. That's the acceptance test.

---

## After this sprint

- Microsoft Graph (mail + calendar) — ~10 days, slots into `connections` as new provider (phase 3)
- Multi-account UI polish (add/remove/archive flows beyond basic) — 1-2 days
- Context-aware morning cast modes (per-context, quiet hours) — 3 days
- Refresh-token lifecycle job — 1-2 days
- Teams (exploratory, tenant-blocked for many) — 5-10 days
