# aiPal — Implementation Roadmap

> A phased plan to build aiPal from a blank Laravel 13 app into a complete, self-hostable personal assistant.
> Companion doc to [FEATURES.md](./FEATURES.md).

---

## Guiding Principles

1. **Multi-provider from day 1** — Anthropic, OpenAI, DeepSeek, xAI, Gemini, **Ollama (local)** all first-class. No vendor lock-in. Provider + model is configurable per tool and per request.
2. **Ship in vertical slices** — each phase delivers a working, usable feature end-to-end (DB → backend → UI).
3. **Docker-first** — every phase is testable via `docker compose up`. ARM64 multi-arch from the first image build.
4. **Test coverage ≥ 80%** — feature tests for every endpoint, unit tests for services.
5. **No premature abstraction** — pluggable tool system is formalized only in Phase 8 after we have real tools to generalize from.
6. **Data portability** — personas, notes, memory, and config are all exportable as JSON.
7. **Small multi-user from v1** — designed for 1–5 users per instance (you + family/trusted). No org/team complexity. Each user has isolated memory, notes, persona.
8. **Simple stack wins** — Blade + Livewire + Alpine + Tailwind. No SPA build complexity.

---

## Tech Stack (locked)

| Layer | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| AI SDK | Laravel AI SDK v0 (`laravel/ai`) |
| DB | PostgreSQL 16 + pgvector 0.7+ |
| Cache/Queue | Redis 7 |
| Queue monitoring | Laravel Horizon |
| Web server | Nginx + PHP-FPM |
| Frontend | Blade + Alpine.js + Tailwind v4 (Livewire for reactive bits) |
| Auth | Laravel Sanctum (PAT for API, session for web) |
| Testing | PHPUnit 12 |
| Formatter | Laravel Pint |
| Container | Docker Compose, multi-arch (amd64 + arm64) |

---

## AI Provider Matrix

All providers accessed via Laravel AI SDK. Configuration lives in `config/ai.php` and `.env`.

| Provider | Chat | Embeddings | Vision | STT | TTS | Image Gen |
|---|---|---|---|---|---|---|
| Anthropic (Claude) | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| OpenAI | ✅ | ✅ | ✅ | ✅ (Whisper) | ✅ | ✅ (DALL-E 3) |
| DeepSeek | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| xAI (Grok) | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Gemini | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ (Imagen) |
| **Ollama (local)** | ✅ | ✅ (nomic-embed) | ✅ (llava) | ❌ | ❌ | ❌ |
| ElevenLabs | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |

**Ollama note**: runs as a separate container in `docker-compose.yml` (optional, profile-gated — `docker compose --profile ollama up`). Enables fully offline operation on a Raspberry Pi or air-gapped server. Falls back to a cloud provider if Ollama isn't running.

**Default routing strategy:**
- **Chat**: user-configurable; default Anthropic claude-sonnet-4-6
- **Embeddings**: OpenAI `text-embedding-3-small` (cheapest, good enough)
- **STT**: OpenAI Whisper
- **TTS**: OpenAI TTS (fallback ElevenLabs)
- **Image gen (avatar)**: OpenAI DALL-E 3 (fallback Gemini Imagen)

**Fallback chain**: each capability has a primary + secondary provider configured. On failure or rate limit, fall back automatically.

---

# Phases

## Phase 0 — Foundation (Week 1) ✅ COMPLETED

**Goal:** A running containerized Laravel app with DB, queues, and CI.

### Tasks
- [x] `docker-compose.yml` — app, postgres (w/ pgvector), redis, nginx
- [x] `Dockerfile` — multi-stage, multi-arch (amd64 + arm64)
- [x] `.env.example` with all provider keys scaffolded
- [x] Laravel install: Horizon, Sanctum, Pint
- [x] pgvector migration: enable extension
- [x] Health check endpoint (`/healthz`)
- [x] CI: GitHub Actions → lint (Pint) + test (PHPUnit) + build images
- [x] `README.md` with install instructions for VPS / laptop / Raspberry Pi

### Deliverable
`docker compose up` → Laravel welcome page, queue worker running, DB+Redis connected.

---

## Phase 1 — Multi-Provider AI Core (Week 2) ✅ COMPLETED

**Goal:** Working chat with pluggable provider switching. Foundation for every other feature.

### Tasks
- [x] `config/ai.php` — provider/model matrix
- [x] `ChatAgent` — Laravel AI SDK agent with conversation memory
- [x] Migration: `agent_conversations` (handled by Laravel AI SDK)
- [x] Streaming response endpoint (SSE) — `POST /api/v1/chat`
- [x] CLI: `php artisan ai:test {provider}` — quick provider verification

### Deliverable
`curl -X POST /api/v1/chat -d '{"message":"hi"}'` streams a response. Switching provider in `.env` works without code changes.

---

## Phase 2 — Web UI, Auth & Multi-User (Week 3) ✅ COMPLETED

**Goal:** Chat in a browser; invite a second user.

### Tasks
- [x] Sanctum auth: register, login, PAT generation
- [x] First-user = admin (automatic on fresh install)
- [x] Admin-only invite flow (generate signed invite link, no public signup)
- [x] User scoping: conversations belong to users
- [x] Middleware: web session + API PAT (`admin` middleware alias)
- [x] Chat UI: Blade + Livewire + Alpine + Tailwind
  - [x] Conversation list sidebar (Alpine-managed, API-backed)
  - [x] Message stream with SSE token-by-token rendering
  - [x] Markdown rendering (marked.js)
  - [x] New conversation button
- [x] Dark mode (class-based, localStorage persistent)

### Deliverable
Open `localhost`, log in, chat with any configured provider.

---

## Phase 3 — Persona & Onboarding (Week 4) ✅ COMPLETED

**Goal:** First-run wizard builds the assistant's identity.

### Tasks
- [x] Migration: `personas` table (one per user)
- [x] `Persona` model + `User::persona()` hasOne relation
- [x] Onboarding wizard flow (Livewire multi-step, 3 steps):
  1. Name
  2. Communication style (tone, formality, humor)
  3. Backstory / role
- [x] `PersonaGenerator` service — turns wizard answers into system prompt via LLM
- [x] Inject persona as system prompt in every chat call (`ChatAgent::withSystemPrompt`)
- [x] `EnsurePersonaExists` middleware — redirects to `/onboarding` if no persona
- [x] Settings page: edit persona attributes + system prompt, regenerate prompt
- [x] Export persona → JSON download (`GET /persona/export` route, plain `<a>` link)
- [x] Import persona ← JSON upload (Livewire `WithFileUploads`, validates all fields before applying)
- [ ] `AvatarGenerator` service — DEFERRED (next session)

### Deliverable
On first login, wizard runs. Assistant has a name, personality that persists across conversations. Persona can be exported/imported as JSON from Settings.

---

## Phase 4 — Memory & Embeddings Pipeline (Week 5) ✅ COMPLETED

**Goal:** The assistant remembers you.

### Tasks
- [x] Migration: `memories` table with `vector(1536)` column (pgvector)
- [x] `EmbeddingService` — embed text via OpenAI `text-embedding-3-small`
- [x] `MemoryExtractorAgent` — structured output agent, extracts durable facts post-conversation
- [x] `MemoryRetriever` — semantic search via `whereVectorSimilarTo`, injects top-k into system prompt
- [x] Queue job: `ExtractMemoriesJob` dispatched after each chat turn
- [x] UI: `/memories` Livewire page — list, search, delete memories
- [x] Export memories → JSON (`GET /memories/export`)
- [x] Import memories ← JSON (re-embeds on import, deduplicates)

### Deliverable
Tell the assistant "I'm a backend engineer, I use Laravel." New conversation later — it knows this without being told again.

---

## Phase 5 — RAG & Knowledge Base (Week 6) ✅ COMPLETED

**Goal:** Upload your own docs, ask questions over them.

### Tasks
- [x] Migration: `documents`, `document_chunks` (with `vector(1536)` on chunks, SQLite-safe fallback)
- [x] Upload endpoint: MD, TXT, code files (PHP, JS, Python, Go, etc.) — max 20 MB
- [x] `DocumentParser` — reads and normalizes file content by extension / MIME type
- [x] `DocumentChunker` — splits by paragraphs, then fixed size with 100-char overlap
- [x] `DocumentIngestionJob` — chunk + embed async via Horizon (2 tries, 300s timeout)
- [x] `SearchKnowledgeBase` tool — vector search scoped to user's documents, injected into `ChatAgent`
- [x] `ChatAgent` implements `HasTools`, receives user via `withUser()`
- [x] UI: `/documents` Livewire page — upload, list with status, search, delete
- [x] Documents nav link added to all sidebar pages (chat, memories, settings)

### Deliverable
Upload a markdown or code file. Ask "what does the spec say about auth?" — the assistant searches your knowledge base and cites the source document in its answer.

---

## Phase 6 — Voice (STT + TTS) (Week 7) ✅ COMPLETED

**Goal:** Talk to the assistant, hear it back.

### Tasks
- [x] Browser audio recorder (MediaRecorder API, push-to-talk)
- [x] Upload endpoint: audio → Whisper → transcript
- [x] TTS endpoint: text → audio stream (OpenAI TTS default, ElevenLabs option)
- [x] UI: mic button on chat input, play button on responses
- [x] Per-session TTS toggle
- [x] Persona voice selection (OpenAI TTS voices / ElevenLabs voice cloning)

### Deliverable
Click mic → speak → transcript appears → response played back in persona's voice.

---

## Phase 7 — Notes & Reminders & Tasks (Week 8) ✅ COMPLETED

**Goal:** Core productivity features via natural language.

### Tasks
- [x] Migration: `notes`, `reminders`, `tasks`
- [x] Notes: CRUD + embeddings for semantic search
- [x] Reminders: natural language parsing via `ReminderParserAgent` (structured output)
  - [x] Scheduled command: `reminders:dispatch` runs every minute via `withoutOverlapping()`
  - [x] `EmailChannel` (Laravel Mail via `ReminderNotification`)
  - [x] `WebhookChannel` (user-defined URL, POST JSON payload)
  - [x] Per-reminder channel selection
- [x] Tasks: CRUD + priority + due date + complete/uncomplete
- [x] AI tools: `CreateNote`, `SearchNotes`, `CreateReminder`, `CreateTask`, `ListTasks` injected into `ChatAgent`
- [x] UI: `/productivity` Livewire page with Tasks / Reminders / Notes tabs

### Deliverable
"Remind me to review PR tomorrow at 9am" → reminder created → notification delivered at 9am.

---

## Phase 8 — Pluggable Tool System (Week 9) ✅ COMPLETED

**Goal:** Formalize the tool architecture so integrations plug in cleanly.

### Tasks
- [x] `AiTool` abstract base class (extends `Laravel\Ai\Contracts\Tool`, adds metadata + execution logging)
- [x] Tool registry (`ToolRegistry`) — auto-discovery via `app/Ai/Tools/*.php` filesystem scan
- [x] JSON schema generation — inherited from Laravel AI SDK `Tool` contract
- [x] Tool execution logging (`tool_executions` table) — hooked in `AiTool::handle()`
- [x] Refactor Phase 4–7 tools into `AiTool` base
- [x] Tool enable/disable UI in settings (toggle switches per tool, grouped by category)
- [x] Per-tool enable/disable via `user_tool_settings` table; disabled tools excluded from `ChatAgent`

### Deliverable
Adding a new tool = creating one class in `app/Ai/Tools/` that extends `AiTool`. No core changes needed.

---

## Phase 9 — Daily Briefing & Calendar (Week 10) ✅ COMPLETED

**Goal:** Morning summary + calendar awareness.

### Tasks
- [x] Google OAuth integration (`GoogleToken` model, `GoogleAuthController`, `GoogleClientFactory`)
- [x] Calendar read API — `GoogleCalendarService` (list events, token refresh)
- [x] `GoogleCalendarTool` (uses Phase 8 AiTool interface, category: calendar)
- [x] `DailyBriefingAgent` + `DailyBriefingJob` — scheduled job generates morning summary
- [x] Delivery: email via `DailyBriefingNotification`
- [x] User-configurable: enable/disable, delivery time, timezone (Settings page)
- [x] Google Calendar connect/disconnect from Settings
- [x] Scheduler: everyMinute check dispatches job at configured time per user
- [x] Docs: `docs/google-oauth-setup.md`

### Deliverable
Every morning at your configured time, you get a summary of today's tasks, events, and anything flagged.

---

## Phase 10 — Messaging Bots: WhatsApp + Telegram (Week 11) ✅ COMPLETED

**Goal:** Chat with the assistant from WhatsApp and Telegram.

### Tasks
- [x] `MessagingChannel` interface (`app/Contracts/MessagingChannel.php`)
- [x] Webhook endpoint: `POST /webhooks/telegram` (CSRF excluded, secret-token validated)
- [x] Telegram: Bot API (`TelegramService`) — sendMessage, setWebhook, deleteWebhook
- [x] Message → conversation routing (telegram_chat_id → user, `/start` returns chat ID)
- [x] Send reply via Telegram Bot API
- [x] Rate limiting: 10 messages/minute per chat_id
- [x] `TelegramChannel` notification channel — reminder delivery via Telegram
- [x] Settings UI: Telegram chat ID link/unlink
- [x] `telegram:set-webhook` artisan command
- [x] WhatsApp: Meta Cloud API (`WhatsAppService`, `WhatsAppWebhookController`, `WhatsAppChannel`)
- [x] `/webhooks/whatsapp` webhook endpoint
- [x] Setup guide in docs (`docs/telegram-setup.md`, `docs/whatsapp-setup.md`)
- [x] Support voice notes (STT pipeline) in Telegram/WhatsApp

### Deliverable
Send a Telegram or WhatsApp message → assistant responds with full memory/tool access. Reminders delivered to both channels. Voice notes pending.

---

## Phase 11 — Dev Integrations (Week 12)

**Goal:** GitLab, Jira, Email triage, Code review.

### Tasks
- [x] `GitLabTool` — list MRs, summarize, create issues, recent commits
- [x] `JiraTool` — JQL queries, create/update issues, transitions ✅
- [ ] `EmailTool` — Gmail API + IMAP; list, summarize, draft replies
- [ ] `CodeReviewTool` — diff in, structured feedback out
- [ ] `MeetingNotesTool` — paste notes, extract actions → create tasks
- [ ] `TerminalHelperTool` — shell command explain/suggest

### Deliverable
"Summarize my open MRs" / "What's in my sprint?" / "Review this diff" all work.

---

## Phase 12 — Polish, Docs, Release (Week 13)

**Goal:** v1.0 ready for public release.

### Tasks
- [ ] Full README with screenshots
- [ ] Install guides: VPS (DigitalOcean/Hetzner), laptop (Docker Desktop), Raspberry Pi
- [ ] `ARCHITECTURE.md` — diagrams, data flow
- [ ] Contribution guide
- [ ] Issue templates, PR template
- [ ] Changelog
- [ ] Security policy
- [ ] Release v1.0 Docker images (multi-arch) to GHCR
- [ ] Demo video
- [ ] Launch post (Reddit r/selfhosted, HN, Laravel News)

### Deliverable
Public repo with clean install path. Anyone can `docker compose up` and have their own aiPal in < 10 minutes.

---

# Cross-Cutting Concerns

## Security (applies every phase)
- Never log API keys or tokens
- All provider keys in `.env`, validated at boot
- Rate limit every endpoint (Laravel `RateLimiter`)
- CSRF on web, PAT scopes on API
- Sanitize markdown output (prevent XSS in rendered AI responses)
- Webhook signature validation (WhatsApp)
- Audit log for tool executions

## Testing (applies every phase)
- Feature test for every endpoint
- Unit test for every service class
- Fake provider responses for deterministic AI tests
- E2E smoke test via Dusk or Playwright for critical flows
- CI gate: no merge below 80% coverage on changed files

## Observability
- Laravel Pail for dev logs
- Horizon dashboard for queues
- Sentry integration (optional, env-gated)
- Per-provider latency + error metrics

## Data Portability
Every phase that introduces user data adds export/import:
- Phase 3: persona JSON
- Phase 4: memories JSON
- Phase 5: documents (ZIP with embeddings)
- Phase 7: notes, reminders, tasks JSON
- A single `/api/v1/export/all` endpoint bundles everything

---

# Locked Decisions

1. ✅ **Multi-user** — small-scale multi-user from v1 (1–5 users/instance, invite-based). Each user has isolated memory, notes, persona.
2. ✅ **License** — **AGPL-3.0** (protects against closed-source forks)
3. ✅ **Ollama** — supported as 6th provider from Phase 1 (optional Docker profile)
4. ✅ **Frontend** — Blade + Livewire + Alpine + Tailwind v4
5. ✅ **Reminder delivery** — pluggable channels: email, WhatsApp, Telegram, generic webhook. User configures which channels are enabled per reminder. At least one channel must be configured.
6. ✅ **No hosted demo** — local / self-host only.

---

# Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Provider API changes | Abstract behind `AiProviderService`; version-pin SDK |
| pgvector performance at scale | Add HNSW index; benchmark at 100k vectors |
| Raspberry Pi memory limits | Make STT/TTS optional; document min specs per feature |
| WhatsApp API costs / approval | Support both Twilio (faster) and Meta direct (cheaper) |
| Scope creep | Stick to phase gates; no feature jumps ahead |
| LLM hallucination in RAG | Always cite sources; show retrieved chunks in UI |

---

# Timeline Summary

| Phase | Weeks | Milestone |
|---|---|---|
| 0 | 1 | Docker stack running |
| 1 | 2 | Multi-provider chat API |
| 2 | 3 | Web UI + auth |
| 3 | 4 | Persona & onboarding |
| 4 | 5 | Long-term memory |
| 5 | 6 | RAG + knowledge base |
| 6 | 7 | Voice (STT/TTS) |
| 7 | 8 | Notes / reminders / tasks |
| 8 | 9 | Pluggable tool system |
| 9 | 10 | Daily briefing + calendar |
| 10 | 11 | WhatsApp bot |
| 11 | 12 | Dev integrations |
| 12 | 13 | v1.0 release |

**Total: ~13 weeks for v1.0** (assuming ~15–20 hrs/week solo)

---

# Next Action

**Phase 6 — Voice (STT + TTS)**

Phases 0–5 are complete. Next: browser push-to-talk (MediaRecorder), audio upload → Whisper transcription, TTS endpoint streaming audio back, mic button + play button in chat UI, per-session TTS toggle, and persona voice selection.
