# Changelog

All notable changes to aiPal are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

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
