# Top 3 Features — Build Plan

**Status 2026-04-24:** All three features shipped in-session. Feature #1 lives on Telegram + email (WhatsApp deferred to v1.1 pending Meta template approval). Feature #2 shipped with Cloudflare Email Worker architecture — requires user to deploy the Worker + set `INBOUND_EMAIL_HMAC_SECRET`. Feature #3 existed in codebase; this work added the per-user voice-daily-limit safeguard.

**Follow-up next-sprint roadmap (contexts + connections) captured in [sprint-contexts-connections.md](sprint-contexts-connections.md).**



**Status:** Drafted 2026-04-24 · focus for next sprint
**Source:** Advisor + aiPal/main analysis. Background in `docs/ideas/` (if present) and in-conversation context.

## Product framing

aiPal is **not** a developer tool with extras — it's a **multi-channel, ambient, proactive personal chief-of-staff** that happens to have dev integrations. The moat is WhatsApp/Telegram + persistent memory + scheduled push + calendar/location/weather context. The big LLM apps (ChatGPT, Claude, Gemini) are pull-based and single-channel; they cannot copy this without rebuilding their product. These three features double down on that moat.

---

## The three features

1. **Morning Focus Cast** — 8:47am WhatsApp push: top-3 things today, based on calendar + tasks + overnight messages + weather. A decision, not a newsletter.
2. **Forward-to-aiPal** — Magic email address per user. Forward any email/message → auto-extract tasks, reminders, memories, notes. Reply with a 1-line confirmation.
3. **Voice on WhatsApp** — Inbound voice note → STT → treat as normal chat → reply (text in v1, TTS later).

---

## Build order (NOT ranking order)

1. **Morning Focus Cast first.** Smallest code delta. Proves the push-to-WhatsApp path end-to-end. The scheduling + WhatsApp-outbound plumbing here is reused by features 2 and 3.
2. **Forward-to-aiPal second.** Reuses the existing `MemoryExtractorAgent` extraction pattern. Net-new: inbound email route + DNS.
3. **Voice on WhatsApp last.** Highest user impact but most moving parts (audio format, latency, STT cost). Don't start here.

---

## Pre-coding facts (verified 2026-04-24)

| Question | Finding | Impact |
|---|---|---|
| WhatsApp provider? | Meta Cloud API (`graph.facebook.com/v20.0`) | Outbound requires approved **template** if >24h since user's last inbound. Morning cast triggers this. **Decision (2026-04-24): defer WhatsApp template to v1.1; ship morning cast on Telegram + email first.** |
| WhatsApp webhook live? | Yes — `/webhooks/whatsapp` POST + GET verify | Voice-in has a receiver already. |
| WhatsApp media download? | `WhatsAppService` already fetches media via `graph.facebook.com/{mediaId}` | Voice note download is ~10 lines. |
| Daily briefing exists? | Yes — `DailyBriefingJob` + `DailyBriefingNotification`, **email-only** (`toMail`) | Morning cast = reframe (top-3) + add WhatsApp channel. Not a greenfield job. |
| STT wired? | `Laravel\Ai\Transcription` working in `TranscribeController` (web voice input) | Voice-on-WhatsApp reuses it. |
| Inbound email configured? | **No.** Only outbound (SMTP/Mailgun/Postmark) | Forward-to-aiPal needs inbound webhook (Mailgun/Postmark/SES) + DNS MX. Flag to user before building. |

---

## Minimum viable slice per feature

### 1. Morning Focus Cast (MVS)

- Extend `DailyBriefingJob` (or new `MorningCastJob` — decide on read) to:
  - Query today's calendar events, overdue + due-today tasks, unread reminders, overnight unread messages, one-line weather.
  - LLM synthesizes to **≤3 bullets + 1-line intro.** Strict prompt, low temp.
  - Dispatch at user's `briefing_timezone` + chosen time (default 08:47 local).
- New `MorningCastNotification` with `via()` preference order: **Telegram → email** (WhatsApp added in v1.1 once a UTILITY template is approved).
- New `TelegramChannel` notification channel class that calls `TelegramService::sendMessage`.
- Keep email fallback (`toMail`) for users without Telegram.
- **Not in v1:** adjustable tone/length, per-day variation, user editing the prompt, WhatsApp delivery.
- **v1.1 (after template approval):** `WhatsAppChannel` + `WHATSAPP_MORNING_CAST_TEMPLATE` env + preference order becomes Telegram → WhatsApp → email. Optional freeform-first attempt within 24h window with error-131047 fallback.

### 2. Forward-to-aiPal (MVS)

- User gets `forward-{random-32-char-token}@inbound.aipal.app`. Token stored on `users.inbound_email_token` (unique, nullable). Generate on user settings page button "Enable email forwarding".
- **Inbound provider: Cloudflare Email Routing + Email Worker** (user has credits, zero cost, DNS stays on Cloudflare). Worker receives raw MIME, validates SPF/DKIM from headers, HMAC-signs a POST to aiPal.
- New `InboundEmailController` at `/webhooks/email/inbound` — verifies HMAC (shared secret `INBOUND_EMAIL_HMAC_SECRET`), looks up user by token in the to-address, rejects SPF/DKIM failures.
- **Prereq (user task):** confirm `aipal.app` (or chosen domain) is on Cloudflare. If not, delegate `inbound.aipal.app` subdomain to Cloudflare nameservers.
- Single LLM pass with structured output: classifier returns `{ kind: task|reminder|memory|note, payload: {...} }`. Reuse `MemoryExtractorAgent` patterns.
- Persist to the right table, send a text-only email reply: "Got it — saved as a task: '...'."
- **Not in v1:** attachments (log & skip), calendar invites, contact updates, WhatsApp-forward ingestion.

### 3. Voice on WhatsApp (MVS)

- `WhatsAppWebhookController` — detect `type=audio`, download media via existing service, save to `storage/app/whatsapp-voice/{id}.ogg`.
- Pass file to `Laravel\Ai\Transcription` → transcript string.
- Feed transcript into existing chat pipeline exactly as if user typed it. Reply comes back as **text** in v1.
- **Rate limit from day one:** config key `WHATSAPP_VOICE_DAILY_SECONDS_PER_USER` (default 300s/day). Track in `tool_executions` or dedicated table. Over limit → polite text reply.
- **Not in v1:** voice reply (TTS), streaming transcription, language auto-detect.

---

## Risks & mitigations

| Risk | Mitigation |
|---|---|
| **STT cost runaway** — one user spams voice notes | Rate limit per-user daily seconds. Hard cap, not "we'll add later." |
| **Privacy** — voice content routed through third-party STT | Privacy policy update + user-facing disclosure in settings before enabling voice. |
| **Forward-address guessability** — spam/injection magnet | Token must be unguessable (32+ chars, CSPRNG). Never use username. SPF/DKIM required. |
| **Timezone bugs** — morning cast fires at wrong time | Resolve `briefing_timezone` per-user at dispatch time, not at job creation. Test with 3 non-UTC users before ship. |
| **WhatsApp template rules** — outbound outside 24h session window fails | Morning cast MUST use pre-approved UTILITY template. Voice-reply uses session window (safe). Forward-to-aiPal replies via email, not WhatsApp. |
| **Prompt injection via forwarded email** | Forwarded content is *never* treated as instructions — only as data to classify. System prompt must explicitly say so. |

---

## Concrete TODO for next sessions

**Before coding feature #1:**
- [ ] Read `DailyBriefingJob` + `DailyBriefingNotification` + `DailyBriefingAgent` end-to-end
- [ ] Read `TelegramService::sendMessage` signature + existing notification wiring
- [ ] Decide: extend `DailyBriefingJob` or new `MorningCastJob`? (Advisor: extend if briefing is already per-user scheduled, else new.)

**Feature #1 tasks (Telegram + email, WhatsApp deferred):**
- [ ] Implement `TelegramChannel` notification channel
- [ ] Refactor/add job for top-3 synthesis
- [ ] Prompt tuning — target ≤3 bullets, one-line intro
- [ ] Feature test: scheduled job fires at user's local time, routes to Telegram if linked, else email
- [ ] Settings UI toggle: "Morning focus cast" on/off + time picker + channel preference

**Feature #1 v1.1 (later, requires Meta template approval):**
- [ ] Request WhatsApp UTILITY template in Meta Business Manager
- [ ] `WhatsAppChannel` + `WHATSAPP_MORNING_CAST_TEMPLATE` env
- [ ] Optional freeform-first attempt with error-131047 fallback to template

**Feature #2 tasks (Cloudflare Email Worker):**
- [x] Domain = `samirai.xyz`, inbound subdomain configurable via `INBOUND_EMAIL_DOMAIN` env (default `inbound.samirai.xyz`)
- [ ] User task: confirm domain is on Cloudflare and enable Email Routing catch-all → Worker
- [x] Email Worker (`workers/inbound-email/`) — parses MIME, HMAC-signs SHA-256, POSTs to aiPal
- [x] Migration `users.inbound_email_token` (unique nullable 64-char)
- [x] `InboundEmailController` + HMAC verification + SPF/DKIM gate + per-user rate limit
- [x] `ForwardedEmailClassifierAgent` (task/reminder/memory/note) + `ForwardedEmailProcessor`
- [x] Settings UI: "Enable forwarding" button → shows address + regenerate/disable
- [x] 6 feature tests covering HMAC reject, SPF reject, unknown recipient, and 3 classification paths

**Feature #3 tasks:**
- [x] Webhook + transcription already wired in `ProcessWhatsAppMessageJob::resolveText` (pre-existing)
- [x] Per-user daily voice-note cap via `Cache` counter with 24h TTL (`services.whatsapp.voice_daily_limit`, default 30)
- [x] Kill-switch: setting limit to 0 disables voice input entirely with a clear user message
- [x] User-facing disclosure in Settings → WhatsApp copy
- [x] Feature tests: blocked when at cap, disabled at 0, counter increments on first voice use
- [ ] Later: add reply-as-voice (TTS) path, streaming transcription, multi-language autodetect

---

---

# Next sprint — Contexts, Connections, and Microsoft

Captured 2026-04-24 after advisor pass on multi-inbox + Microsoft integration.

## Why this next

Current model hardcodes one-of-each integration as scalar columns on `users`
(`google_token`, `telegram_chat_id`, `inbound_email_token`, etc.). User's real
workflow has **three distinct contexts** that share none of that:
1. Full-time work — work email + calendar + Slack + Jira
2. Freelance — multiple concurrent clients, each with their own calendar/email
3. Personal — private email, calendar, WhatsApp, Telegram, notes

"Work / personal" tagging is too coarse (misses freelance) and "free-form tag"
is a mess. Advisor's design: **two-level hierarchy** — a closed-enum `kind`
drives filtering, a free-form `name` is user-facing.

## Data model

### `contexts` (new)
```
id, user_id
kind         enum("work" | "freelance" | "personal")
name         user-facing, free-form ("Acme Corp", "Project Phoenix", "Personal")
slug         short identifier, derived from name, editable ("acme", "phoenix")
color        hex — for UI badges
is_default   one per user (fallback when no context specified)
archived_at  soft-archive when a contract/job ends — stops daily pushes, stays searchable
timestamps
```

### `connections` (new — replaces scalar columns on `users`)
```
id, user_id
context_id    FK → contexts.id
provider      "google" | "microsoft" | "inbound_email" | "telegram" | "whatsapp" | "jira" | ...
capabilities  JSON: ["mail", "calendar"] — one OAuth can cover both
label         user-facing ("samir@acme.com")
identifier    provider-specific primary ID (email / phone / chat ID)
credentials   encrypted JSON (tokens + refresh) — use Laravel encrypted cast
is_default    one per {user, capability} pair
enabled       boolean
metadata      JSON — scopes granted, Microsoft tenant ID, etc.
last_synced_at
timestamps
```

### Add `context_id` (nullable) to existing tables
`tasks`, `reminders`, `notes`, `memories`, `agent_conversations` — so every
persisted artifact carries its context. Nullable + backfill → default context.

## Context inference (critical UX)

Every ingested artifact should *guess* its context and confirm only if unsure:
- Inbound email from `@acme.com` → Work context if work Gmail is on Acme
- Calendar event on the Phoenix calendar → Phoenix context (follows the connection)
- WhatsApp voice note → user's default context (usually Personal)
- Forwarded email to `forward-{token}-phoenix@…` → Phoenix context (explicit override)

Rules stored per-context as a small JSON `inference_rules` blob (sender domains,
calendar IDs, keyword patterns). Start with hardcoded inference + user override.

## Forward-to-aiPal — context-scoped addresses

Instead of one `forward-{token}@…`, generate one per context:
- `forward-{token}-work@inbound.samirai.xyz`
- `forward-{token}-phoenix@inbound.samirai.xyz`
- `forward-{token}-personal@inbound.samirai.xyz`

Same user token, slug suffix = explicit context tag. `InboundEmailController`
parses the suffix, routes to that context. If suffix is missing, uses default.

## Morning Focus Cast — context modes

New user preference "cast mode":
- **"One cast covering everything"** (default) — current behavior, with context labels on bullets when multiple contexts have content
- **"One cast per context"** — separate Telegram push for Work, each Freelance, Personal
- **"Work-hours only"** — work + active freelance, skip personal

Per-context quiet hours solves "don't page me about Phoenix at 9pm Sunday."

## Chat tools get a `context` arg

`usage_insights`, future `inbox_query`, future `calendar_query` — all accept
optional `context` arg. "What's on my *Phoenix* calendar Thursday?" routes
through. Default = user's default context. Cross-context queries ("what's my
total commitment this week?") are first-class, not filtered.

## Microsoft integration — slots into contexts cleanly

Once the `contexts` + `connections` refactor is in, Microsoft is just a new
`provider` value:

- **Microsoft Graph API** — unified surface (Outlook mail, calendar, Teams, OneDrive)
- **Auth**: OAuth via MS identity platform. Personal MSA straightforward;
  work/school (Azure AD) ~20–40% hit tenant-admin blocks
- **Teams**: separate effort, ~50% of corporate tenants block third-party. Treat
  as exploratory, don't promise publicly
- **Graph throttles more aggressively than Google** — respect `Retry-After`,
  exponential backoff

## Phase plan

| Phase | Work | Days |
|---|---|---|
| **1** | `contexts` + `connections` tables, migrations, backfill, refactor call sites | 5–7 |
| **2** | Multi-account UI polish: add/remove/move/archive/default switcher, color picker | 1–2 |
| **3** | Microsoft Graph: mail + calendar as a provider under the new model | ~10 |
| **4** | Forward-to-aiPal: slug-suffix routing + context inference for inbound mail | 2 |
| **5** | Context-aware morning cast: modes + quiet hours per context | 3 |
| **6** | Teams (exploratory, tenant-dependent) | 5–10 |

Advisor's recommendation: **Option B** — bundle phase 1 + phase 4 into one
sprint (~8 days). End state: user's three contexts working end-to-end for
inbound email + morning cast, abstraction proven against a real second use case
(multiple inbound addresses). Microsoft becomes a clean extension next sprint.

## Sharp edges

- **Encryption cast** on `connections.credentials` — non-negotiable
- **Refresh-token lifecycle** — background job per-connection, notify user on
  breakage, don't let silent auth failures corrupt morning cast
- **Default switcher must be reversible** — users regret initial "which is
  default" within a week
- **Context archival, never deletion** — attached memories/tasks/notes must
  survive a closed contract; use `archived_at`
- **Test debt** — 234 tests today, many touch `users.google_token` etc.
  directly. Budget heavy test-update time; this is the longest part of phase 1

## Open decision needed before sprint starts

**Per-context morning casts from day one, or one combined cast with context
labels?**
- Combined = ships in ~8 days, user will ask for per-context split within a week
- Per-context = +2–3 days, matches the "work cast 8am, personal 9am weekends" need

---

## Out of scope (for this sprint)

Per advisor's explicit guidance, **do not** build these now — they dilute the WhatsApp-first identity:
- More dev tools (standup writer, PR triage, etc.)
- Mobile app
- Browser extension
- Voice on web (already works via `TranscribeController`; don't reinvest)

These can come after the top 3 are in production and showing retention.
