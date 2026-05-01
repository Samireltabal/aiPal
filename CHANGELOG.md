# Changelog

All notable changes to aiPal are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

---

## [1.3.0] — 2026-05-01

Major release — Personal CRM, Browser Extension, Microsoft Graph, Contexts & Connections.

### Added

**Personal CRM**
- `people` and `interactions` tables — full address book with emails, phones, tags, birthday, notes, custom JSONB fields
- Auto-population from forwarded email and Gmail drafts (email-only; Telegram/WhatsApp are user↔bot)
- `/people` Livewire UI — list with search, tag filter, stale filter, inline create, CSV/JSON export, bulk tag, merge duplicates
- `/people/{id}` detail page — profile editor with tag autocomplete, emails/phones with primary toggle, log-interaction form, timeline of past interactions
- `PersonMerger` service — transactional merge of duplicates (tags union, scalar backfill, emails/phones/interactions reassigned, max(last_contact_at), soft-delete)
- 7 AI tools: `FindPerson`, `ListPeople`, `CreatePerson`, `UpdatePerson`, `LogInteraction`, `RecentInteractions`, `FindStaleContacts`
- REST API endpoints under `/api/v1/people` and `/api/v1/interactions`
- Daily `people:birthday-check` command (07:00, 7-day lookahead, idempotent per-year)
- Dashboard "People to reconnect with" widget
- `/productivity?reminder_for_person={id}` deep-link prefill from PersonDetail

**Browser Extension (Chrome MV3)**
- `extension/` Vite + crxjs + TypeScript scaffold
- Sanctum 'extension' ability + 60/min throttle on `/api/v1/extension/capture`
- Polymorphic capture endpoint: memory | task | note | reminder
- Inline embedding with zero-vector fallback
- Popup with 4 tabs: Ask (client-side `/chat?prefill=`), Capture, Quick Note, Quick Reminder
- Right-click context menu + `Cmd+Shift+A` keyboard shortcut
- Settings → Browser Extension page with token issuance
- GitHub release workflow attaches versioned `.zip`

**Microsoft Graph integration**
- OAuth via v2.0 `/common` endpoint, multi-account, id_token-based identification
- `OutlookTool` — list / read / search mail (read-only by decision)
- `OutlookCalendarTool` — list + create events with per-turn record guardrail
- `MicrosoftConnectionAuth` token-refresh helper

**Contexts & Connections**
- New `contexts` and `connections` tables; one-shot backfill migration
- Context-aware `currentContext()` / `applyConversationContext()` API on User
- Per-conversation context_id persistence in chat / WhatsApp / Telegram
- `switch_context` LLM tool + `ResolvesContextHint` trait on integration tools
- Multi-account integrations for Google / GitHub / GitLab / Jira via polymorphic `connections` table
- `inference_rules` JSON column on contexts (sender-domain routing logic)
- `/contexts` page with inference-rules UI
- `connections:refresh-tokens` scheduled command (every 5 min, 10-min buffer)
- Per-turn record-creation guardrail (`MAX_RECORDS_PER_TURN = 3`)
- `deleteAllPendingReminders` bulk-clear action

**AI Configuration**
- All AI providers/models fully configurable via `.env` — no hardcoded `#[Provider]` / `#[Model]`
- New env vars: `MEMORY_EXTRACTOR_*`, `DAILY_BRIEFING_*`, `REMINDER_PARSER_*`, `AI_DEFAULT_EMBEDDINGS_PROVIDER`, `AI_EMBEDDING_MODEL`, `AI_EMBEDDING_DIMENSIONS`, `AI_DEFAULT_STT_PROVIDER`, `AI_STT_MODEL`, `AI_DEFAULT_AUDIO_PROVIDER`, `AI_TTS_MODEL`
- Settings page AI configuration section with live view of active providers/models
- Graceful fallbacks when an agent's provider fails (memory retriever, daily briefing)
- Dashboard active-model badge

### Fixed
- `tool_executions.duration_ms` overflow — column widened from `smallint` to `unsignedInteger`
- GitLab MR scope leak — `scope=assigned_to_me` no longer applied to project-specific queries
- `AI_DEFAULT_PROVIDER` case sensitivity normalised to lowercase in config
- TTS/STT model arguments conditionalised (only passed when set)

### Changed
- Dropped legacy `users.github_token`, `gitlab_*`, `jira_*` scalar columns (data migrated to polymorphic `connections`)
- Test suite grew from ~268 → 425+ passing tests

---

## [1.0.0] — 2026-04-20

### Added

**Core AI & Chat**
- Multi-provider chat with streaming SSE responses
- Providers: Anthropic (Claude), OpenAI (GPT), DeepSeek, xAI (Grok), Gemini, Ollama (local/offline)
- Per-conversation provider and model switching
- Persona system — give your assistant a name, personality, tone, and backstory
- AI-generated system prompt from persona attributes
- Persona export/import as JSON

**Memory & Knowledge**
- Long-term semantic memory via pgvector — facts extracted and recalled across conversations
- RAG knowledge base — upload documents (MD, TXT, code files), ask questions over them
- Memory and document export/import

**Voice**
- Push-to-talk in the browser (MediaRecorder → Whisper STT)
- Text-to-speech responses (OpenAI TTS / ElevenLabs)
- Per-session TTS toggle; persona voice selection
- Voice note support in Telegram and WhatsApp

**Productivity**
- Notes — create and search by natural language
- Reminders — natural language parsing, delivered via email / Telegram / WhatsApp / webhook
- Tasks — create, prioritize, complete via chat
- Daily briefing — morning summary emailed at your configured time

**Integrations**
- Google Calendar — read events, include in daily briefing and chat context
- Google OAuth connect/disconnect from Settings
- Telegram bot — full chat with memory and all tools; reminders delivered to Telegram
- WhatsApp bot — full chat via Meta Cloud API (no Twilio); voice notes supported
- Jira — JQL search, create and update issues
- GitLab — list MRs, summarize, create issues, view recent commits
- Gmail — list inbox, read emails, create draft replies

**AI Tools (pluggable, Phase 8+)**
- `SearchKnowledgeBase`, `CreateNote`, `SearchNotes`, `CreateReminder`, `CreateTask`, `ListTasks`
- `GoogleCalendar`, `Gmail`, `JiraSearch`, `JiraCreateIssue`, `JiraUpdateIssue`
- `GitLabMR`, `GitLabCreateIssue`, `GitLabCommits`
- `CodeReview` — AI-powered structured diff review with severity and line numbers
- `MeetingNotes` — extract action items from meeting notes, auto-create tasks
- `TerminalHelper` — explain shell commands or suggest commands for a described task
- Per-user tool enable/disable in Settings

**Platform**
- Multi-user with admin-only invite flow (signed invite links)
- User-scoped memory, notes, persona, conversations
- REST API with personal access tokens (Laravel Sanctum)
- Laravel Horizon for queue monitoring
- Docker Compose setup (app, Postgres+pgvector, Redis, Nginx, Caddy for HTTPS, Horizon, Scheduler)
- Multi-arch Docker images (amd64 + arm64 — works on Raspberry Pi 4/5)
- Ollama optional profile for fully offline local model support
- GitHub Actions CI — lint (Pint) + test (PHPUnit) + Docker build

---

[1.0.0]: https://github.com/Samireltabal/aiPal/releases/tag/v1.0.0
