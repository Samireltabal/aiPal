# Getting Started with aiPal

Welcome! This guide helps new users get aiPal up and running quickly.

## Prerequisites
- Docker (Desktop on macOS/Windows or Engine on Linux)
- At least one AI provider API key (recommended: Anthropic Claude or OpenAI)
- For offline: Ollama support via Docker profile

## Quick Local Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Samireltabal/aiPal.git
   cd aiPal
   ```

2. Copy and edit environment file:
   ```bash
   cp .env.example .env
   # Edit .env and add at least one key, e.g.
   # ANTHROPIC_API_KEY=sk-ant-...
   # OPENAI_API_KEY=sk-...  # for embeddings and voice
   ```

3. Start the stack:
   ```bash
   docker compose up -d
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

4. Open http://localhost in your browser and complete the onboarding wizard (create admin account, set persona).

## Environment Configuration

The `.env` file controls all settings. Key variables include:

- **AI keys**: `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, `GEMINI_API_KEY`, etc. (at least one required)
- **App basics**: `APP_URL=http://localhost`, `APP_KEY` (auto-generated), `APP_ENV=local`
- **Database/Redis**: `DB_*` and `REDIS_*` (usually fine with Docker defaults)
- **Mail** (for reminders, briefings): `MAIL_MAILER`, `MAIL_HOST`, etc. Use a service like Resend or Mailgun in prod.
- **Voice/Embeddings**: `OPENAI_API_KEY` is required for Whisper STT and pgvector embeddings.
- **Ollama**: Set `AI_DEFAULT_PROVIDER=ollama` and use the ollama profile in docker-compose.

For the complete list and production values, inspect `.env.example` and `.env.production.example` in the repo root. Changes to AI models require container rebuild (`docker compose up -d --build`).

See the [AI Model Configuration section in README](../README.md#ai-model-configuration) for per-function provider settings.

## First Steps After Setup
- Start a chat in the browser UI.
- Try uploading a document for RAG (Knowledge Base).
- Configure voice in Settings if desired.
- See the [New User Guide in README](../README.md#new-user-guide) (includes First Chat Examples) for more.

## Onboarding Tips
- Set your persona name, personality, and avatar in Settings → Persona. This affects how the AI responds and speaks.
- Toggle available AI tools/functions in Settings → AI Functions (e.g., enable/disable tasks, reminders, calendar).
- Review the [AI Model Configuration](../README.md#ai-model-configuration) table to customize providers/models per function if needed.

## Next
- For production/VPS deployment, see [deploy-vps.md](./deploy-vps.md)
- For Raspberry Pi / offline, see README sections on ARM64 and Ollama.
- Explore integrations via the Setup Guides table in README.
- Learn about the Knowledge Base in [knowledge-base.md](./knowledge-base.md)

If you run into issues, check the [Troubleshooting Guide](./troubleshooting.md).
