# Personal CRM — Resumption Checkpoint

**Last touched:** 2026-04-29
**Owner branch:** `feat/personal-crm-services` (PR #18, stacked on `feat/personal-crm` / PR #17)

## Status at a glance

| Phase | What | Status |
|---|---|---|
| **A** | Schema + models + factories + 11 tests | ✅ PR #17 (open, base = main) |
| **B** | Service layer (resolver, recorder, staleness, summary job/agent) | ✅ PR #18 |
| **C** | Auto-population (inbound email + outbound Gmail draft) | ✅ PR #18 |
| **D** | REST API + 7 AI tools | ✅ PR #18 |
| **E** | Livewire UI: `/people` list + `/people/{id}` detail | ⬜ next |
| **F** | Birthday reminders + dashboard widget + sidebar + README | ⬜ next |

Test suite: **405 pass / 1 skip / 0 fail** (was 343 at main; +62 in this branch).

## Resumption checklist (when you're ready to continue)

1. **Merge order**: PR #17 first, then PR #18. After PR #17 merges:
   ```bash
   git checkout feat/personal-crm-services
   git fetch origin
   git rebase origin/main          # diff narrows to just Phase B–D
   git push --force-with-lease
   ```
2. After PR #18 merges:
   ```bash
   git checkout main && git pull --ff-only
   git checkout -b feat/personal-crm-ui
   ```
3. Pick up Phase E (see "Next work" below).

## Decisions already locked in

| # | Decision | Notes |
|---|---|---|
| 1 | Channels for auto-population | **Email only** (inbound + Gmail outbound draft). Telegram/WhatsApp dropped — those are user-↔-bot, not third-party contacts. |
| 2 | Tags | Free-form strings, jsonb array on `people.tags` |
| 3 | Custom fields | jsonb blob on `people.custom` |
| 4 | Person ↔ Context | One context per person; `Person::context_id` nullable |
| 5 | Interaction summaries | LLM-generated via queued `SummarizeInteractionJob` (configurable model, falls back to 280-char truncation) |
| 6 | Outbound Gmail capture | Draft-only — flagged `metadata.draft=true`. Real send capture is a 5-line upgrade when actual send is added. |
| 7 | Search | `LOWER(col) LIKE` driven by Postgres pg_trgm GIN indexes; portable on SQLite for tests |
| 8 | Soft-delete people | Yes, via `deleted_at` |
| 9 | Birthday reminders | Yes — wire up daily scheduled command (Phase F) |

## Next work — Phase E (Livewire UI)

### `/people` list page (`app/Livewire/People.php` + `resources/views/livewire/people.blade.php`)
- Search box (debounced) → calls existing `GET /api/v1/people?q=`
- Tag filter chips — derived from `Person::pluck('tags')->flatten()->unique()`
- "Stale" toggle (uses existing `?stale=1` param)
- Mobile-responsive (`flex-col md:flex-row`)
- Empty state: link to "Add a person manually" or "Forward an email to your inbound address"
- New-person button → opens an inline form (or modal) that posts to `POST /api/v1/people`

### `/people/{id}` detail page (`app/Livewire/PersonDetail.php` + view)
- Header: avatar (gravatar via primary email hash if no `photo_url`), name, company · title, tag chips
- Inline-editable fields (notes, tags, birthday, custom)
- Email + phone list with primary-toggle controls
- **Interactions timeline** (descending occurred_at):
  - Channel icon, direction arrow, relative time
  - Subject + summary (or skeleton state if `SummarizeInteractionJob` hasn't run yet)
  - Click row to expand `raw_excerpt`
- "Log interaction" form (inline) — POST to `/api/v1/people/{id}/interactions`
- "Schedule follow-up" button — deep-link to `/productivity?reminder_for_person={id}` and have the productivity page pre-fill the reminder body with "Follow up with {name}" (productivity will need a small mount() patch — same pattern as the chat `?prefill=` we did for the extension)

### Routes
Add to `routes/web.php` under the `persona` middleware group:
```php
Route::get('/people', \App\Livewire\People::class)->name('people');
Route::get('/people/{id}', \App\Livewire\PersonDetail::class)->name('people.show');
```

## Next work — Phase F (Birthday + widget + polish)

### `people:birthday-check` scheduled command
- `app/Console/Commands/CheckBirthdays.php` — daily
- Find people whose birthday is **today + N days** (N from `config('people.birthday_lookahead_days', 7)`)
- For each match, idempotently create a `Reminder` (channel=`web` or user's `default_reminder_channel`) with body = "It's {name}'s birthday on {date}"
- Idempotency key: `metadata.birthday_year_for_person={person_id}-{year}` so the same year doesn't double-fire

Register in `routes/console.php`:
```php
Schedule::command('people:birthday-check')->dailyAt('07:00');
```

### Dashboard widget
- Edit `app/Livewire/Dashboard.php` to call `(new ContactStaleness)->count($user)` and `->query($user)->limit(5)->get()`
- Add a card on the dashboard view: "X people stale — top 5 list, link to /people?stale=1"

### Sidebar nav
- Edit `resources/views/components/nav-sidebar.blade.php` — add People entry between Productivity and Workflows
- Pattern: pick an existing icon (e.g. users icon from heroicons)
- Include in `active="people"` mapping

### README + memory
- Append "Personal CRM" entry to README features list (under Platform or new "Productivity" section)
- Update `~/.claude/projects/-Users-samir-assistant-aiPal/memory/project_status.md` to mark Phase 14 #8 (Personal CRM) as ✅ shipped

## Untouched files of interest (in case you forget)

| File | Why it matters here |
|---|---|
| `config/people.php` | Tunables (transactional regex, staleness days, summary model) |
| `app/Modules/People/PeopleServiceProvider.php` | Loads the API routes |
| `app/Modules/People/Services/PersonResolver.php` | Find-or-create logic — UI `POST /api/v1/people` doesn't go through this; the Livewire form should call it for symmetry once email is provided |
| `app/Ai/Services/ToolRegistry.php` | Auto-discovers tools from `app/Ai/Tools/*.php` — no manual registration needed when adding new tools |

## Smoke tests to run before declaring done

- [ ] Forward a real email to your inbound aiPal address → `Person` row appears with the right context, `Interaction` logged with `summary` filled in (after job runs)
- [ ] Ask in chat "find Sara" → `find_person` fires
- [ ] Ask "who haven't I talked to in 90 days?" → `find_stale_contacts` fires
- [ ] Use `GmailTool` to draft a reply → check `/people` for the recipient with `metadata.draft=true` interaction
- [ ] Visit `/people` → list, search, filter, paginate
- [ ] Visit `/people/{id}` → timeline + log interaction
- [ ] Set birthday on a person to today+1 → `people:birthday-check` (run manually) → reminder appears
- [ ] Dashboard shows stale-contacts widget
