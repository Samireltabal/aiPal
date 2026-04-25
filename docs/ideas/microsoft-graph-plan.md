# Microsoft Graph Integration Plan

**Status:** draft for review
**Author:** assistant + Samir
**Date:** 2026-04-25

The user works primarily on Microsoft 365 (work account). Adding Microsoft
Graph as a first-class provider is the largest unlock left after the multi-
account refactor. This doc proposes the shape *before* any code is written.

---

## 1. Goal

Reach feature parity with the Google integration for the work-context use
case:

- Read mail (Outlook) — list, get, search
- Read/write calendar — list events, create event
- Send mail (optional, phase 2)
- Multi-account: a user can attach multiple Microsoft tenants/accounts
  (personal + work + client tenants), each as its own `Connection` row,
  routed by context the same way Google is.

Non-goals (for first PR):
- Teams chat, OneDrive, SharePoint, To-Do — defer until requested.
- Webhook-based change notifications — polling is fine for v1.

## 2. Storage shape (no schema changes needed)

Reuse the existing `connections` table — no migrations required:

| Column        | Value                                                 |
| ------------- | ----------------------------------------------------- |
| `provider`    | `Connection::PROVIDER_MICROSOFT` (already defined)    |
| `capabilities`| `[CAPABILITY_MAIL, CAPABILITY_CALENDAR]` per scopes   |
| `identifier`  | UPN / email from `/me` (e.g. `samir@acme.com`)        |
| `label`       | User-editable (e.g. "Acme Work")                      |
| `credentials` | encrypted JSON: `access_token`, `refresh_token`, `expires_at`, `scopes`, `tenant_id`, `home_account_id` |
| `metadata`    | `{ tenant_name, account_type: 'work'|'personal' }`    |
| `context_id`  | links to a Context (default = user's default context) |
| `is_default`  | one per provider per user                             |

This mirrors the Google migration (`migrate_google_tokens_to_connections`)
exactly. No `microsoft_tokens` table — straight to `connections` from day
one.

## 3. OAuth flow (MSAL / Azure AD v2.0 endpoint)

Use the v2.0 `/common/oauth2/v2.0/authorize` endpoint so the same app reg
handles personal *and* work/school accounts.

Required env:

```
MS_GRAPH_CLIENT_ID=
MS_GRAPH_CLIENT_SECRET=
MS_GRAPH_REDIRECT_URI=https://app.example.com/auth/microsoft/callback
MS_GRAPH_TENANT=common   # 'common' | 'organizations' | 'consumers' | <tenant-id>
```

Scopes (initial):

```
offline_access
openid email profile
Mail.Read
Calendars.ReadWrite
```

Add `Mail.Send` only when phase 2 ships.

Routes:

- `GET  /auth/microsoft` → redirect to consent URL (state = signed user id)
- `GET  /auth/microsoft/callback` → exchange code, hit `/me` for identifier,
  upsert `Connection` keyed by `(user_id, provider=microsoft, identifier)`
- `DELETE /auth/microsoft/{connection}` → delete row

Mirror `GoogleAuthController` 1:1. No new architectural patterns.

## 4. Service layer

New files (all extend the same patterns we already have for Google):

```
app/Services/MicrosoftConnectionAuth.php   # token-refresh + Graph client builder
app/Services/MicrosoftGraphMailService.php # list/get/search messages
app/Services/MicrosoftGraphCalendarService.php # list/create events
```

`MicrosoftConnectionAuth::authenticatedClient(Connection)` is the analogue
of `GoogleConnectionAuth::authenticatedClient` — refreshes via
`mergeCredentials` when `expires_at` is past, returns an authenticated
Graph client (HTTP via Guzzle is enough; the official `microsoft/microsoft-graph` SDK
is heavyweight — start without it unless we need typed payloads).

## 5. AI tools

New tools, one per capability, following the existing trait + schema pattern:

```
app/Ai/Tools/Outlook/OutlookTool.php           # list/search recent mail
app/Ai/Tools/Outlook/OutlookSendTool.php       # phase 2
app/Ai/Tools/Outlook/OutlookCalendarTool.php   # list events
app/Ai/Tools/Outlook/OutlookCreateEventTool.php
```

Each tool:
- uses `ResolvesContextHint` trait (so users can say "from work inbox")
- resolves connection via `pickConnectionForHint($user, 'microsoft', $contextHint)`
- returns `'Microsoft account not connected'` when no connection exists
- enforces the same per-turn record guardrail when it writes data
  (the create-event tool counts toward `incrementCreatedRecordsThisTurn`)

Register in the same place GmailTool / GoogleCalendarTool are wired up.
Tool descriptions should explicitly say "for work email" / "for Outlook" so
the LLM disambiguates from Gmail.

## 6. Settings UI

Reuse the multi-account list pattern from the Google section in
`resources/views/livewire/settings.blade.php`:

- "Microsoft accounts" card
- For each connection: identifier, label, "Make default", "Remove"
- "Add another Microsoft account" → `/auth/microsoft`

`Livewire/Settings.php` gains:
- `microsoftConnections` computed prop
- `removeIntegrationConnection`/`setDefaultIntegrationConnection` already
  generic — should work as-is. Verify before assuming.

## 7. Tests

Mirror the Google test files:

```
tests/Feature/Microsoft/MicrosoftAuthControllerTest.php
tests/Feature/Microsoft/OutlookToolTest.php
tests/Feature/Microsoft/OutlookCalendarToolTest.php
```

Mock `MicrosoftConnectionAuth` / Graph HTTP at the service boundary — same
shape as `GoogleCalendarToolTest`.

Coverage target: same 80%+ rule as the rest of the codebase.

## 8. Phasing

| Phase | Scope                                                | Est.   |
| ----- | ---------------------------------------------------- | ------ |
| 1a    | OAuth + Connection storage + Settings UI             | 1 day  |
| 1b    | OutlookTool (read mail) + tests                      | 1 day  |
| 1c    | OutlookCalendarTool (read events) + tests            | 1 day  |
| 1d    | OutlookCreateEventTool + guardrail wiring + tests    | 1 day  |
| 2     | OutlookSendTool + Mail.Send scope                    | 0.5 d  |
| 3     | Token-refresh background job (shared with Google)    | 1 day  |

Phase 1 = ~4 days for a usable work-mail/calendar integration.
Total to "feature complete v1" = ~6.5 days.

## 9. Open questions for review

1. **App registration ownership** — single Azure AD app reg with
   multi-tenant consent, or one per deployment? (Recommendation: single
   multi-tenant app reg, store `tenant_id` per connection.)
2. **Personal vs work accounts** — accept both via `/common`, or restrict
   to work via `/organizations`? (Recommendation: `/common`; the user's
   identifier carries the distinction.)
3. **Send mail** — needed in phase 1 or defer to phase 2? (Recommendation:
   defer — read coverage is the unlock.)
4. **Naming** — `OutlookTool` vs `MicrosoftMailTool`? Consistency check
   against `GmailTool` (provider-flavored) suggests `OutlookTool`.
5. **Webhook subscriptions** — out of scope for v1, but the Graph
   `/subscriptions` endpoint is the right path later for push instead of
   poll. Keep token shape forward-compatible.

## 10. What this doc is *not*

- Not implementation. No code lands until this plan is reviewed.
- Not a full Graph SDK abstraction — only the surface we need.
- Not a Teams/SharePoint/Drive plan — separate doc when those are needed.
