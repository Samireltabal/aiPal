# aiPal

A self-hostable, open-source personal assistant built with Laravel 13. Supports multiple AI providers, long-term memory, voice, notes, reminders, calendar, GitLab, Jira, WhatsApp, Telegram, and more.

**License:** AGPL-3.0 | **Stack:** Laravel 13 · PHP 8.4 · PostgreSQL + pgvector · Redis · Livewire

---

## Features

- Multi-provider AI (Anthropic, OpenAI, DeepSeek, xAI, Gemini, Ollama)
- Long-term memory via pgvector semantic search
- RAG — chat over your own documents
- Voice input (STT) and output (TTS)
- Notes, reminders, tasks, daily briefing
- Calendar, GitLab, Jira, and email integrations
- WhatsApp & Telegram bots
- Persona — name, personality, and AI-generated avatar
- REST API with personal access tokens
- Fully self-hostable with Docker

See [FEATURES.md](./FEATURES.md) for the complete feature list.

---

## Requirements

| | Minimum |
|---|---|
| Docker | 24+ with Compose v2 |
| RAM | 2GB (4GB recommended, 4GB required for Ollama) |
| Disk | 10GB |
| AI key | At least one provider key — or use Ollama for fully local/offline use |

---

## Installation

The steps are identical on every platform. Pick your platform below for any prerequisites, then follow the same three-step setup.

---

### VPS (DigitalOcean, Hetzner, Linode, etc.)

**Prerequisites:**
```bash
# Install Docker (Ubuntu/Debian)
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER && newgrp docker
```

**Setup:**
```bash
git clone https://github.com/your-username/aipal.git
cd aipal
cp .env.example .env
# Edit .env — set APP_URL=http://your-server-ip and add an AI key
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open `http://your-server-ip` and complete the onboarding wizard.

> **HTTPS on a VPS:** Point a domain at your server and add a reverse proxy (Nginx + Certbot or Caddy) in front of port 80.

---

### Local Laptop (macOS / Linux / Windows)

**Prerequisites:**
- macOS / Windows: install [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Linux: `curl -fsSL https://get.docker.com | sh`

**Setup:**
```bash
git clone https://github.com/your-username/aipal.git
cd aipal
cp .env.example .env
# Edit .env — add an AI provider key
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open **http://localhost** in your browser.

---

### Raspberry Pi (ARM64)

The Docker images are multi-arch — no special steps needed. Works on Pi 4 and Pi 5.

**Minimum spec:** Raspberry Pi 4 · 4GB RAM · 32GB SD/SSD

**Prerequisites (Raspberry Pi OS):**
```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER && newgrp docker
```

**Setup:**
```bash
git clone https://github.com/your-username/aipal.git
cd aipal
cp .env.example .env
# Edit .env — add an AI key, or use Ollama for fully offline (see below)
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open `http://raspberrypi.local` (or the Pi's IP) from another device on the network.

---

## Local Models — Fully Offline (Ollama)

Works on any platform. Required for air-gapped or internet-free setups.

```bash
docker compose --profile ollama up -d
docker compose exec ollama ollama pull llama3.2
docker compose exec ollama ollama pull nomic-embed-text
```

Set in `.env`:
```
AI_DEFAULT_PROVIDER=ollama
```

> On Raspberry Pi 4 (4GB RAM), run a smaller model: `ollama pull llama3.2:1b`

---

## Development

```bash
composer run dev
```

This starts: Laravel dev server, queue worker, Pail log viewer, and Vite in one command.

**Run tests:**
```bash
php artisan test --compact
```

**Format code:**
```bash
vendor/bin/pint
```

---

## Architecture

See [ARCHITECTURE.md](./ARCHITECTURE.md) for system design, module layout, data flow, and DB schema.

---

## Roadmap

See [PLAN.md](./PLAN.md) for the full 13-phase build plan.

---

## Contributing

1. Fork the repo
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Follow the module structure in `app/Modules/`
4. Write tests (80%+ coverage required)
5. Run `vendor/bin/pint` before committing
6. Open a PR against `develop`

---

## License

[AGPL-3.0](./LICENSE) — you can use, modify, and self-host freely. If you run a modified version as a service, you must publish your changes.
