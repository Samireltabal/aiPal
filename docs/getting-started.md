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

## First Steps After Setup
- Start a chat in the browser UI.
- Try uploading a document for RAG (Knowledge Base).
- Configure voice in Settings if desired.
- See the [New User Guide in README](../README.md#new-user-guide) for more.

## Next
- For production/VPS deployment, see [deploy-vps.md](./deploy-vps.md)
- For Raspberry Pi / offline, see README sections on ARM64 and Ollama.
- Explore integrations via the Setup Guides table in README.

If you run into issues, check the [Troubleshooting Guide](./troubleshooting.md).
