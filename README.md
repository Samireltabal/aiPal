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

- \"Remind me to call my mom tomorrow at 9am via Telegram\"
- \"Create a task: finish the Q3 report, priority high, due Friday\"
- \"What do you remember about my project with Sarah from last week?\"
- \"Upload my notes? [paste or use knowledge base] Tell me the key points from my meeting notes on AI agents\"
- \"Will it rain this weekend in my current location?\"
- \"Summarize my Gmail inbox and suggest replies\"

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
- **Reminders** — \"remind me tomorrow at 9am\" — delivered via email, Telegram, or WhatsApp
- **Tasks** — create, prioritize, and complete tasks by chatting
- **Daily briefing** — morning summary of your day via email, configurable time and timezone. See the [Daily Briefing guide](./docs/daily-briefing.md) for setup and customization.
- **Personal CRM** — auto-populated people + interactions from forwarded email and Gmail drafts; `/people` UI, stale-contact dashboard widget, daily birthday reminders, AI tools (`find_person`, `find_stale_contacts`, `log_interaction`, etc.). See the [Personal CRM guide](./docs/personal-crm.md) for full details.
- **Location & Weather** — capture your current location (via browser geolocation, WhatsApp/Telegram share, or pasted Maps link) for context, weather queries (\"will it rain?\"), and improved daily briefings. View and manage in Settings; also exposed via REST API.

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

Edit `.env` — set `APP_URL=http://localhost:8080` and `APP_PORT=8080` to avoid port 80 conflicts (common on developer machines), and at minimum set one AI provider key:

```env
APP_URL=http://localhost:8080
APP_PORT=8080
ANTHROPIC_API_KEY=sk-ant-...   # Sign up at https://anthropic.com, get key from https://console.anthropic.com/settings/keys | or use OPENAI_API_KEY, etc.
OPENAI_API_KEY=sk-...          # Sign up at https://openai.com, get key from https://platform.openai.com/api-keys | Required for embeddings (memory/RAG) + STT (Whisper)
```

```bash
docker compose up -d
docker compose ps  # ensure postgres and redis are healthy (Up (healthy))
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
# Quick test (should return 200 OK or redirect)
curl -I http://localhost:8080
```

**Note:** If you encounter permission issues with Docker on Linux, ensure your user is part of the `docker` group by running `sudo usermod -aG docker $USER` and logging out and back in.

Open **http://localhost:8080** and complete the onboarding wizard.

---

## Deploy to a VPS (with HTTPS)

1. Clone the repo on your VPS.
2. `cp .env.production.example .env`
3. Edit `.env` with your domain, AI keys, etc.
4. `docker compose -f docker-compose.prod.yml up -d --build`
5. Run migrations: `docker compose exec app php artisan migrate --force`
6. Set up HTTPS with Caddy or Nginx reverse proxy (recommended: [Caddy](https://caddyserver.com/)).
7. (Optional) Set up Horizon dashboard with basic auth.

**Example Caddyfile:**
```
aipal.example.com {
  reverse_proxy localhost:8080
}
```

For production scaling, see [docs/deploy-vps.md](./docs/deploy-vps.md).

---

## Setup Guides

### Telegram Bot

1. Create a bot with [@BotFather](https://t.me/botfather).
2. Set `TELEGRAM_BOT_TOKEN` in `.env`.
3. Run `php artisan telegram:webhook` (or manually set webhook to `${APP_URL}/telegram/webhook`).
4. Test: message your bot.

See [docs/telegram-setup.md](./docs/telegram-setup.md).

### WhatsApp Bot

1. Create Meta app at [developers.facebook.com](https://developers.facebook.com).
2. Add WhatsApp product, get credentials.
3. Set in `.env`: `WHATSAPP_*`.
4. Verify webhook: `php artisan whatsapp:verify`.
5. Test: send message to your phone ID.

See [docs/whatsapp-setup.md](./docs/whatsapp-setup.md).

### Google OAuth (Calendar + Gmail)

1. Create OAuth app in [Google Cloud Console](https://console.cloud.google.com).
2. Add redirect URI `${APP_URL}/auth/google/callback`.
3. Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`.

See [docs/google-oauth-setup.md](./docs/google-oauth-setup.md).

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md).

## License

AGPL-3.0. See [LICENSE](LICENSE) for details.