# aiPal — Architecture

> Companion to [FEATURES.md](./FEATURES.md) and [PLAN.md](./PLAN.md).
> Describes how aiPal is organized, how modules communicate, and what lives where.

---

## 1. Architectural Principles

1. **Modular Monolith, DDD-light** — one Laravel app, multiple bounded contexts as modules. No microservices.
2. **Modules own their data, contracts, and UI** — everything a feature needs lives under `app/Modules/{Name}/`.
3. **Communicate via contracts, not models** — modules depend on interfaces in `Core/Contracts/` or `{OtherModule}/Domain/Contracts/`, never directly on each other's Eloquent models.
4. **Ports & Adapters for externals** — AI providers, messaging APIs, calendar APIs, etc. are adapters behind interfaces. Swappable.
5. **Thin controllers, explicit services** — controllers validate/transform; domain logic lives in application services.
6. **Immutable DTOs at module boundaries** — readonly PHP 8 classes for anything crossing a module boundary.
7. **No cross-module DB joins** — if two modules need each other's data, expose a read model or service method.

---

## 2. High-Level System Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          Entry Points (Interfaces)                      │
│  Web UI (Blade+Livewire)  │  REST API (/api/v1)  │  WhatsApp  Telegram  │
└──────────────┬──────────────────┬──────────────────────┬────────────────┘
               │                  │                      │
               └──────────────────┴──────────────────────┘
                                  │
                    ┌─────────────▼───────────────┐
                    │     Application Services     │  (per module)
                    │  ChatService, MemoryService  │
                    │  ReminderService, ...        │
                    └─────────────┬───────────────┘
                                  │
      ┌─────────────────────┬─────┴─────┬────────────────────┐
      │                     │           │                    │
┌─────▼──────┐      ┌───────▼──────┐  ┌─▼────────────┐  ┌────▼──────────┐
│   Domain    │      │   Core       │  │ Infrastructure│  │  External     │
│  (entities, │      │ (AI provider │  │  (Eloquent,   │  │  (OpenAI,     │
│   VOs,      │      │  abstraction,│  │   Redis,      │  │  Anthropic,   │
│  contracts) │      │  tool reg.)  │  │   pgvector)   │  │  Telegram...) │
└─────────────┘      └──────────────┘  └───────────────┘  └───────────────┘
                                  │
                    ┌─────────────▼───────────────┐
                    │      Storage & Runtime      │
                    │  PostgreSQL+pgvector │ Redis │
                    │  Horizon (queues) │ Storage  │
                    └─────────────────────────────┘
```

---

## 3. Module Layout

Every module follows the same internal structure:

```
app/Modules/{Name}/
├── Domain/
│   ├── Contracts/           # Interfaces this module exposes to other modules
│   ├── Entities/            # Pure PHP entities (no Eloquent)
│   ├── ValueObjects/        # Readonly value types
│   └── Exceptions/
├── Application/
│   ├── Services/            # Orchestration & use cases
│   ├── DTOs/                # Input/output DTOs for services
│   └── Jobs/                # Queue jobs
├── Infrastructure/
│   ├── Models/              # Eloquent models
│   ├── Repositories/        # If repo abstraction adds value (not always)
│   └── Adapters/            # External service adapters
├── Http/
│   ├── Controllers/
│   ├── Requests/            # FormRequest validation
│   ├── Resources/           # API resources
│   ├── Livewire/            # Livewire components
│   └── Middleware/
├── Database/
│   ├── Migrations/
│   ├── Factories/
│   └── Seeders/
├── Routes/
│   ├── web.php
│   └── api.php
├── Views/                   # Blade views scoped to module
├── Tests/
│   ├── Feature/
│   └── Unit/
├── Config/
│   └── {name}.php           # Module-level config (auto-merged with app config)
└── {Name}ServiceProvider.php # Registers bindings, routes, migrations, views
```

**Why migrations per module?** Each module owns its schema; deleting a module is clean. Migrations from all modules are auto-registered by the service provider.

---

## 4. The Modules

### Core Modules (shared foundation)

| Module | Purpose |
|---|---|
| **Core** | Shared contracts (`AiProvider`, `EmbeddingProvider`), base DTOs, app-wide exceptions, module kernel helpers. No business logic. |
| **User** | Users, invites, auth (Sanctum), PAT management, per-user scoping middleware. |
| **Persona** | Soul file, avatar generation, onboarding wizard, export/import. |

### Chat Pipeline

| Module | Purpose |
|---|---|
| **Chat** | Conversations, messages, streaming, system prompt composition. Depends on: Core (AI provider), Persona (inject soul), Memory (retrieve), Knowledge (RAG), AiTools (tool calls). |
| **Memory** | Long-term memory: extract, embed, store (pgvector), retrieve by semantic search. |
| **Knowledge** | Documents, chunks, RAG retrieval, personal wiki. |
| **Voice** | STT (audio → text) + TTS (text → audio) via provider adapters. |

### Productivity

| Module | Purpose |
|---|---|
| **Notes** | CRUD notes with embeddings + semantic search. |
| **Reminders** | Natural-language reminder parsing + pluggable delivery channels (`Email`, `WhatsApp`, `Telegram`, `Webhook`). |
| **Tasks** | Task CRUD, priorities, due dates. |
| **Briefing** | Daily briefing job — aggregates tasks/calendar/notifications into a morning summary. |

### Integrations

| Module | Purpose |
|---|---|
| **Integrations\Calendar** | Google Calendar OAuth + read API. |
| **Integrations\GitLab** | GitLab REST API wrapper + tools. |
| **Integrations\Jira** | Jira REST API wrapper + tools. |
| **Integrations\Email** | Gmail API + IMAP for email triage. |

### Developer Tools

| Module | Purpose |
|---|---|
| **CodeReview** | Diff analyzer using AI. |
| **TerminalHelper** | Shell command explain/suggest. |
| **MeetingNotes** | Extract action items from meeting transcripts. |

### Interfaces / Channels

| Module | Purpose |
|---|---|
| **Messaging\WhatsApp** | Webhook receiver + send adapter (Twilio or Meta). Implements `MessagingChannel` + `ReminderChannel`. |
| **Messaging\Telegram** | Same, via Telegram Bot API. |
| **Api** | Versioned REST API (`/api/v1/*`) — acts as a facade over module services. Uses module HTTP layer where practical. |

### Tooling Framework (Phase 8)

| Module | Purpose |
|---|---|
| **AiTools** | Tool registry, auto-discovery, JSON schema generation, execution pipeline, permission checks. Other modules register tools with this. |

---

## 5. Inter-Module Communication Rules

### ✅ Allowed

- Depend on **contracts** (interfaces) exposed by another module's `Domain/Contracts/`.
- Dispatch **domain events** when a cross-module effect is needed.
- Call another module's **public Application Service** (registered in its ServiceProvider).

### ❌ Forbidden

- Importing another module's Eloquent model directly.
- Writing SQL joining another module's tables.
- Reaching into `Infrastructure/` of another module.

### Example — Chat using Memory

```php
// ❌ BAD — Chat module imports Memory Eloquent model
use App\Modules\Memory\Infrastructure\Models\Memory;
$memories = Memory::where('user_id', $userId)->get();

// ✅ GOOD — Chat depends on Memory's public contract
use App\Modules\Memory\Domain\Contracts\MemoryRetriever;

public function __construct(private MemoryRetriever $memory) {}

$memories = $this->memory->search($userId, $query, limit: 5);
```

---

## 6. The AI Provider Abstraction (Core module)

Since all 6 providers must be first-class, the provider abstraction is the most-touched part of the system.

```
app/Modules/Core/Domain/Contracts/
├── AiProvider.php           # chat(), streamChat()
├── EmbeddingProvider.php    # embed(string): array
├── SttProvider.php          # transcribe(audio): string
├── TtsProvider.php          # synthesize(text): audio
├── ImageProvider.php        # generate(prompt): image
└── VisionProvider.php       # describe(image): string

app/Modules/Core/Infrastructure/Adapters/
├── Anthropic/
├── OpenAi/
├── DeepSeek/
├── Xai/
├── Gemini/
├── Ollama/
└── ElevenLabs/
```

**Resolution strategy:**
`AiRouter` service reads `config/ai.php` → resolves primary provider for the requested capability → falls back through the chain on error/rate-limit.

**Per-call override:**
A `Chat` call can force a specific provider/model via DTO field, overriding defaults. Useful when a tool wants fast/cheap responses (e.g., Haiku for memory extraction).

---

## 7. Database Schema Overview

All tables are prefixed by module. Every user-owned resource has `user_id` (FK, indexed).

```
User module
├── users (id, name, email, password, is_admin, created_at)
├── invites (id, token, email, invited_by, accepted_at, expires_at)
├── personal_access_tokens (Sanctum default)

Persona module
├── personas (id, user_id, name, soul, avatar_path, voice_id, exported_at)

Chat module
├── conversations (id, user_id, title, provider, model, created_at)
├── messages (id, conversation_id, role, content, tool_calls, tokens, created_at)

Memory module
├── memories (id, user_id, content, embedding vector(1536), source_message_id, created_at)
   └── INDEX: hnsw (embedding vector_cosine_ops)

Knowledge module
├── documents (id, user_id, title, source_type, source_path, ingested_at)
├── document_chunks (id, document_id, content, embedding vector(1536), position)
   └── INDEX: hnsw (embedding vector_cosine_ops)

Notes module
├── notes (id, user_id, title, body, embedding vector(1536), tags jsonb, updated_at)
   └── INDEX: hnsw (embedding vector_cosine_ops)

Reminders module
├── reminders (id, user_id, body, remind_at, channels jsonb, delivered_at, status)
├── reminder_deliveries (id, reminder_id, channel, payload, response, delivered_at, error)

Tasks module
├── tasks (id, user_id, title, description, status, priority, due_at, completed_at)

Integrations (one table per integration for credentials)
├── integration_credentials (id, user_id, provider, access_token encrypted, refresh_token encrypted, expires_at, scopes)

Messaging
├── messaging_contacts (id, user_id, channel, external_id, verified_at)
   # maps a phone number or telegram chat_id to a user

AiTools
├── tool_executions (id, user_id, tool, input jsonb, output jsonb, duration_ms, created_at)
   # audit log for tool calls
```

**Vector column notes:**
- Default dim = 1536 (OpenAI `text-embedding-3-small`).
- If users pick a different embedding model, they must re-embed everything (documented in settings).
- Use HNSW indexes over IVFFlat — better quality/latency tradeoff at small scale.

---

## 8. Data Flow Examples

### 8.1 User sends a chat message (web UI)

```
[Browser]
   │ submit message via Livewire
   ▼
[ChatController@stream]
   │ auth + validate
   ▼
[ChatService::stream]
   │
   ├──► PersonaService::getSystemPrompt(user)    → soul + avatar bio
   ├──► MemoryRetriever::search(user, msg)       → top-k memories
   ├──► KnowledgeRetriever::search(user, msg)    → top-k doc chunks
   ├──► ToolRegistry::enabledFor(user)           → JSON schemas
   │
   ▼
[AiRouter::chat(provider: user's default, messages, tools)]
   │ streams tokens
   ▼
[SSE response → Livewire → Browser]
   │
   ▼ (after completion, async)
[ExtractMemoriesJob] → stores durable facts
```

### 8.2 Reminder fires

```
[Scheduler: every minute]
   ▼
[DispatchDueRemindersJob]
   │ finds reminders where remind_at ≤ now() and status = pending
   ▼
[For each reminder]
   │ for each channel in reminder.channels:
   ▼
[ReminderChannelRegistry::resolve(channel)]
   │ → EmailChannel | TelegramChannel | WhatsAppChannel | WebhookChannel
   ▼
[Channel->send(reminder, user)]
   │ records reminder_deliveries row with response/error
```

### 8.3 Incoming Telegram message

```
[Telegram] ──webhook──► [/webhooks/telegram]
   ▼
[TelegramWebhookController]
   │ verify signature, parse update
   ▼
[MessagingContactResolver::resolve(chat_id)]
   │ → User (or reject if unknown)
   ▼
[ChatService::handleIncomingMessage(user, text, attachments)]
   │ reuses the same pipeline as web UI
   ▼
[TelegramChannel::sendReply(chat_id, response)]
```

---

## 9. Security Model

- **Per-user scoping** enforced at the Application Service layer (never trust the HTTP layer alone).
- **Encrypted credentials** for integrations (`Crypt::encrypt` on access/refresh tokens).
- **Webhook signature verification** for WhatsApp + Telegram + generic webhooks (HMAC).
- **Rate limiting** per user + per tool (Laravel `RateLimiter`).
- **Tool permissions** — destructive tools (create Jira issue, send email) require explicit enablement per user.
- **Secret validation on boot** — app refuses to start if required secrets for enabled features are missing.

---

## 10. Configuration Strategy

- `config/ai.php` — providers, models, fallback chains
- `config/modules.php` — enabled/disabled modules per install
- `.env` — credentials only
- **Per-user settings** live in DB (`user_settings` table), not env, so users in the same instance can differ.

---

## 11. Testing Strategy

- **Unit tests** — Domain + Application layers (no DB, no HTTP).
- **Feature tests** — HTTP layer per module, using RefreshDatabase.
- **Integration tests** — real pgvector, real Redis via testcontainers or docker-compose.test.yml.
- **AI provider fakes** — `FakeAiProvider` returns canned responses; used in 95% of tests. Real provider calls only in nightly CI job.
- **Coverage target** — 80% per module, enforced in CI.

---

## 12. Deployment Topology

### Single-server (VPS / laptop / Pi)

```
┌──────────────────────────────────────────┐
│              Docker Compose              │
│                                          │
│  nginx ──► php-fpm (app)                 │
│             │                            │
│             ├──► postgres (pgvector)     │
│             ├──► redis                   │
│             └──► ollama (optional)       │
│                                          │
│  horizon (queue worker)                  │
│  scheduler (cron)                        │
└──────────────────────────────────────────┘
```

- Multi-arch images (`amd64` + `arm64`).
- Ollama is behind an optional Docker Compose profile (`--profile ollama`).
- Scheduler runs via Laravel's built-in `schedule:work`.

---

## 13. What This Architecture Buys Us

- **Delete a feature = delete a folder.** Pulling out `Jira` later is `rm -rf app/Modules/Integrations/Jira` + dropping a few tables.
- **Swap a provider.** OpenAI deprecates a model? Edit one adapter.
- **Add a channel.** New "Slack reminder delivery"? Implement `ReminderChannel`, register it, done. No core changes.
- **Test feature in isolation.** Each module has its own test namespace.
- **Parallel development.** Different modules can be built independently once their contracts are published.

---

## 14. Open Architecture Decisions

1. **Do we need a separate `read model` layer** for cross-module queries (e.g., Briefing needs tasks + events + notifications)?
   → Deferred. Build it when the need is real; a simple service calling other services is fine initially.

2. **Event bus or direct calls?**
   → Direct service calls via contracts for v1. Add Laravel events only if a cross-module async effect is actually needed.

3. **Queue isolation per module?**
   → Single default queue for v1. Split (per-module queues) only if Horizon shows contention.

4. **Module versioning?**
   → No. One app, one version. Feature flags via `config/modules.php`.

---

## 15. Next Steps (to transition into Phase 0)

1. Create `app/Modules/Core/` skeleton + kernel service provider
2. Implement the module auto-discovery pattern (service providers register themselves)
3. Port the User module (Sanctum + invites) as the reference module — other modules follow its shape
4. Then proceed with Phase 0 tasks in [PLAN.md](./PLAN.md)
