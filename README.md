# aiPal

A self-hostable, open-source personal AI assistant built with Laravel 13. Talk to it in the browser, via Telegram, or via WhatsApp. It remembers you, knows your documents, manages your tasks, and connects to your calendar.

**License:** AGPL-3.0 · **Repo:** [github.com/Samireltabal/aiPal](https://github.com/Samireltabal/aiPal)

**Stack:** Laravel 13 · PHP 8.4 · PostgreSQL 16 + pgvector · Redis · Livewire · Tailwind v4 · Docker

**Docker image:** `ghcr.io/samireltabal/aipal:latest` (amd64 + arm64)

---

## New User Guide

### Getting Started

1. **Fork and Clone the Repo**: Ensure you have the repository on your local development environment.
2. **Project Setup**: Follow the Quick Start guide in this README to configure your environment.
3. **Development Process**: See the [CONTRIBUTING.md](./CONTRIBUTING.md) for coding standards and GH flow.
4. **Common Issues:** Refer to the [Troubleshooting Guide](./docs/troubleshooting.md) for resolving frequent setup issues.

### Understanding aiPal

- **AI Functions**: Toggle available AI functions, set up providers, and manage settings in the application dashboard.
- **Integrations**: Follow the [Setup Guides](#setup-guides) for step-by-step integration setup.
- **Customizing the Experience**: Use .env configurations to adjust default models and AI behaviors.

For more detailed instructions, visit the [Documentation Index](./docs/index.md) or browse the [docs](./docs) folder.

### First Chat Examples

Once setup and onboarding are complete, open the chat interface and try these natural language prompts to explore core features:

- "Remind me to call my mom tomorrow at 9am via Telegram"
- "Create a task: finish the Q3 report, priority high, due Friday"
- "What do you remember about my project with Sarah from last week?"
- "Upload my notes? [paste or use knowledge base] Tell me the key points from my meeting notes on AI agents"
- "Will it rain this weekend in my current location?"
- "Summarize my Gmail inbox and suggest replies"

These examples demonstrate memory, tasks, reminders, RAG, location, and integrations. The assistant uses tools automatically when enabled in Settings.

---

## Features

### AI & Chat
- Multi-provider AI — Anthropic (Claude), OpenAI (GPT), DeepSeek, xAI (Grok), Gemini, **Ollama (local/offline)**
- Streaming responses with SSE token-by-token rendering
- Switch provider and model per conversation
- Persona — give your assistant a name, personality, and AI-generated avatar

### Memory & Knowledge
- **Long-term memory** — the assistant remembers facts across conversations (pgvector semantic search)
- **RAG / Knowledge Base** — upload your own documents (MD, TXT, code files) and ask questions over them
- Memory export/import as JSON

### Voice
- **Push-to-talk** — speak in the browser, get a transcript (Whisper STT)
- **Text-to-speech** — assistant replies are read back in your persona's voice (OpenAI TTS / ElevenLabs)
- Voice notes in **Telegram and WhatsApp** — send a voice message, get a text reply

### Productivity
- **Notes** — create and search notes by natural language
- **Reminders** — "remind me tomorrow at 9am" — delivered via email, Telegram, or WhatsApp
- **Tasks** — create, prioritize, and complete tasks by chatting
- **Daily briefing** — morning summary of your day via email, configurable time and timezone. See the [Daily Briefing guide](./docs/daily-briefing.md) for setup and customization.
- **Personal CRM** — auto-populated people + interactions from forwarded email and Gmail drafts; `/people` UI, stale-contact dashboard widget, daily birthday reminders, AI tools (`find_person`, `find_stale_contacts`, `log_interaction`, etc.). See the [Personal CRM guide](./docs/personal-crm.md) for full details.
- **Location & Weather** — capture your current location (via browser geolocation, WhatsApp/Telegram share, or pasted Maps link) for context, weather queries ("will it rain?"), and improved daily briefings. View and manage in Settings; also exposed via REST API.

### Integrations
- **Google Calendar** — read events, include them in daily briefing and chat context
- **Telegram bot** — full chat with memory and tools, reminders delivered to Telegram
- **WhatsApp bot** — full chat via Meta Cloud API (no Twilio required), voice notes supported
- **Webhook channel** — deliver reminders to any URL

### Dev Integrations
- **Jira** — JQL search, create and update issues
- **GitLab** — list MRs, summarize, create issues, view recent commits
- **Gmail** — list inbox, read emails, draft replies (Google OAuth)
- **Code Review** — paste a diff, get structured AI feedback with severity and line numbers
- **Meeting Notes** — paste meeting notes, extract action items, auto-create tasks
- **Terminal Helper** — explain shell commands or get a command suggestion for any task

### AI Tools (pluggable)
All tools can be enabled/disabled per user in Settings:
`SearchKnowledgeBase` · `CreateNote` · `SearchNotes` · `CreateReminder` · `CreateTask` · `ListTasks` · `GoogleCalendar` · `Gmail` · `JiraSearch` · `JiraCreateIssue` · `JiraUpdateIssue` · `GitLabMR` · `GitLabCreateIssue` · `GitLabCommits` · `CodeReview` · `MeetingNotes` · `TerminalHelper` · `FindPerson` · `ListPeople` · `CreatePerson` · `UpdatePerson` · `LogInteraction` · `RecentInteractions` · `FindStaleContacts`

### Platform
- **Multi-user** — invite-only (admin generates signed invite links), each user has isolated memory and persona
- **REST API** with personal access tokens (Sanctum)
- **Horizon** dashboard for queue monitoring
- **Browser extension** (Chrome/Chromium) — capture pages, selections, tasks, notes, reminders to your aiPal in one click. See [`extension/README.md`](extension/README.md).
- Fully self-hostable — no cloud accounts required beyond an AI provider key

---

## Quick Start (Local)

**Prerequisites:** [Docker Desktop](https://www.docker.com/products/docker-desktop/) (macOS/Windows) or `curl -fsSL https://get.docker.com | sh` (Linux)

```bash
git clone https://github.com/Samireltabal/aiPal.git
cd aiPal
cp .env.example .env
```

Edit `.env` — at minimum set one AI provider key (also set `APP_PORT=8080` if port 80 is taken, which is common on developer machines):

```env
ANTHROPIC_API_KEY=sk-ant-...   # or OPENAI_API_KEY, GEMINI_API_KEY, etc.
OPENAI_API_KEY=sk-...          # required for embeddings + STT (Whisper)
APP_PORT=8080                  # optional: change if 80 conflicts
```

```bash
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

**Note:** If you encounter permission issues with Docker on Linux, ensure your user is part of the `docker` group by running `sudo usermod -aG docker $USER` and logging out and back in.

Open **http://localhost** (or http://localhost:8080 if you set APP_PORT) and complete the onboarding wizard.

---

## Deploy to a VPS (with HTTPS)

HTTPS is required for Telegram and WhatsApp webhooks. Caddy handles certificates automatically.

```bash
git clone https://github.com/Samireltabal/aiPal.git
cd aiPal
cp .env.production.example .env
nano .env   # fill in APP_DOMAIN, ACME_EMAIL, keys, passwords
```

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Migrations run automatically on startup. Caddy requests a Let's Encrypt certificate on first boot.

**Register webhooks after deploy:**
```bash
# Telegram
docker compose -f docker-compose.prod.yml exec app php artisan telegram:set-webhook

# WhatsApp — register https://yourdomain.com/webhooks/whatsapp in Meta Developer Portal
```

Full guide: [docs/deploy-vps.md](./docs/deploy-vps.md)

---

## Raspberry Pi (ARM64)

Images are multi-arch — no extra steps needed. Works on Pi 4 and Pi 5.

**Minimum:** Raspberry Pi 4 · 4 GB RAM · 32 GB SD/SSD

```bash
git clone https://github.com/Samireltabal/aiPal.git
cd aiPal
cp .env.example .env
# Edit .env — add AI keys, or use Ollama for fully offline
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

---

## Fully Offline — Local Models (Ollama)

Run with no internet and no cloud AI keys, on any platform including Raspberry Pi.

```bash
docker compose --profile ollama up -d
docker compose exec ollama ollama pull llama3.2
docker compose exec ollama ollama pull nomic-embed-text
```

Set in `.env`:
```env
AI_DEFAULT_PROVIDER=ollama
```

> On Pi 4 (4 GB RAM) use a smaller model: `ollama pull llama3.2:1b`

---

## AI Model Configuration

Each function in aiPal can use a different AI provider and model, configured independently via `.env`. Changes take effect after rebuilding the container.

| Function | Env Variables | Compatible Providers | Notes |
|---|---|---|---|
| **Chat** | `AI_DEFAULT_PROVIDER`<br>`{PROVIDER}_DEFAULT_MODEL` | anthropic, openai, deepseek, xai, gemini, ollama | Main conversation |
| **Memory Extraction** | `MEMORY_EXTRACTOR_PROVIDER`<br>`MEMORY_EXTRACTOR_MODEL` | anthropic, openai, gemini | Requires structured output support |
| **Reminder Parser** | `REMINDER_PARSER_PROVIDER`<br>`REMINDER_PARSER_MODEL` | anthropic, openai, gemini | Requires structured output support |
| **Daily Briefing** | `DAILY_BRIEFING_PROVIDER`<br>`DAILY_BRIEFING_MODEL` | anthropic, openai, deepseek, xai, gemini, ollama | Scheduled morning email. See [setup guide](./docs/daily-briefing.md). |
| **Embeddings** | `AI_DEFAULT_EMBEDDINGS_PROVIDER`<br>`AI_EMBEDDING_MODEL`<br>`AI_EMBEDDING_DIMENSIONS` | openai, ollama, gemini | Changing dimensions requires DB migration + re-ingestion |
| **Voice STT** | `AI_DEFAULT_STT_PROVIDER`<br>`AI_STT_MODEL` | openai, gemini | Voice-to-text transcription |
| **Voice TTS** | `AI_DEFAULT_AUDIO_PROVIDER`<br>`AI_TTS_MODEL` | openai, eleven | Text-to-speech output |

If an agent's provider/model env var is left blank, it falls back to `AI_DEFAULT_PROVIDER` and that provider's default model.

### Provider Default Models

| Provider | Env Variable | Default |
|---|---|---|
| Anthropic | `ANTHROPIC_DEFAULT_MODEL` | `claude-sonnet-4-6` |
| OpenAI | `OPENAI_DEFAULT_MODEL` | `gpt-4o` |
| DeepSeek | `DEEPSEEK_DEFAULT_MODEL` | `deepseek-chat` |
| xAI (Grok) | `XAI_DEFAULT_MODEL` | `grok-2-latest` |
| Gemini | `GEMINI_DEFAULT_MODEL` | `gemini-2.0-flash` |
| Ollama | `OLLAMA_DEFAULT_MODEL` | `llama3.2` |

> The current active configuration is also visible in the app under **Settings → AI Model Configuration**.

---

## Setup Guides

| Integration / Feature | Guide |
|---|---|
| Telegram bot | [docs/telegram-setup.md](./docs/telegram-setup.md) |
| WhatsApp (Meta Cloud API) | [docs/whatsapp-setup.md](./docs/whatsapp-setup.md) |
| Google Calendar & Gmail | [docs/google-oauth-setup.md](./docs/google-oauth-setup.md) |
| Microsoft OAuth (Calendar & Mail) | [docs/microsoft-oauth-setup.md](./docs/microsoft-oauth-setup.md) |
| Browser extension | [docs/browser-extension.md](./docs/browser-extension.md) |
| VPS deployment | [docs/deploy-vps.md](./docs/deploy-vps.md) |
| Daily Briefing | [docs/daily-briefing.md](./docs/daily-briefing.md) |
| Personal CRM | [docs/personal-crm.md](./docs/personal-crm.md) |
| Knowledge Base (RAG) | [docs/knowledge-base.md](./docs/knowledge-base.md) |

## Operations

| Task | Guide |
|---|---|
| Monitoring & health checks | [docs/monitoring.md](./docs/monitoring.md) |
| Backup & restore | [docs/backup-restore.md](./docs/backup-restore.md) |
| Troubleshooting | [docs/troubleshooting.md](./docs/troubleshooting.md) |
| FAQ | [docs/faq.md](./docs/faq.md) |
| Security | [SECURITY.md](./SECURITY.md) |
| User Permissions | [docs/user-permissions.md](./docs/user-permissions.md) |

---

## Development

```bash
composer run dev
```

Starts Laravel dev server, queue worker, Pail log viewer, and Vite — all in one terminal.

```bash
php artisan test --compact   # run tests
vendor/bin/pint              # format code
```

**Requirements:** PHP 8.4, Composer 2, Node 22, PostgreSQL 16 with pgvector, Redis 7

---

## Architecture

See [ARCHITECTURE.md](./ARCHITECTURE.md) for system design, module layout, data flow, and DB schema. For docs overview see [docs/index.md](./docs/index.md).

---

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md) for setup instructions, code style, and PR guidelines. Documentation contributions are welcome — see [docs/index.md](./docs/index.md) and [docs/contribution-guidelines.md](./docs/contribution-guidelines.md).

---

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for the full history of changes.

---

## License

[AGPL-3.0](./LICENSE) — free to use, modify, and self-host. If you run a modified version as a public service, you must publish your changes.
