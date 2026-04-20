# Deploying aiPal to a VPS

This guide covers deploying aiPal to any Linux VPS (DigitalOcean, Hetzner, Vultr, etc.)
with automatic HTTPS via Caddy — required for Telegram and WhatsApp webhooks.

---

## Server Requirements

| Resource | Minimum | Recommended |
|---|---|---|
| CPU | 1 vCPU | 2 vCPU |
| RAM | 1 GB | 2 GB |
| Disk | 20 GB | 40 GB |
| OS | Ubuntu 22.04+ | Ubuntu 24.04 |

---

## Step 1 — Point Your Domain

In your domain registrar's DNS settings, add an **A record**:

```
Type: A
Name: @  (or a subdomain like aipal)
Value: <your-server-ip>
TTL: 300
```

Wait for DNS to propagate (a few minutes to 1 hour) before proceeding.

---

## Step 2 — Install Docker on the Server

SSH into your server and run:

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
newgrp docker
```

Verify:
```bash
docker --version
docker compose version
```

---

## Step 3 — Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/aipal.git
cd aipal
```

---

## Step 4 — Configure Environment

```bash
cp .env.production.example .env
nano .env   # or use vim / your preferred editor
```

**Required values to fill in:**

```env
APP_KEY=          # Generate: php artisan key:generate --show
                  # Or: docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

APP_URL=https://yourdomain.com
APP_DOMAIN=yourdomain.com
ACME_EMAIL=you@example.com      # For Let's Encrypt notifications

DB_PASSWORD=<strong-password>
REDIS_PASSWORD=<strong-password>

ANTHROPIC_API_KEY=sk-ant-...    # At least one AI provider is required
OPENAI_API_KEY=sk-...           # Required for STT (Whisper) and embeddings
```

---

## Step 5 — Build and Start

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

This will:
1. Build the PHP + Node image (~3-5 minutes on first run)
2. Start PostgreSQL and Redis
3. Run database migrations automatically
4. Cache config/routes/views
5. Start Caddy, which requests an HTTPS certificate from Let's Encrypt
6. Start the queue worker (Horizon) and scheduler

**Check everything is running:**
```bash
docker compose -f docker-compose.prod.yml ps
```

All services should show `Up`.

**View startup logs:**
```bash
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f caddy
```

---

## Step 6 — Verify HTTPS

Open `https://yourdomain.com` in your browser. You should see the aiPal login page with a valid HTTPS certificate.

If you see a certificate warning, wait 30-60 seconds — Caddy requests the cert on first startup.

---

## Step 7 — Register Telegram Webhook

```bash
docker compose -f docker-compose.prod.yml exec app php artisan telegram:set-webhook
```

Or manually:
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://yourdomain.com/webhooks/telegram" \
  -d "secret_token=<YOUR_TELEGRAM_WEBHOOK_SECRET>" \
  -d "allowed_updates=[\"message\"]"
```

Verify:
```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

---

## Step 8 — Register WhatsApp Webhook

In the Meta Developer Portal:

1. Go to **WhatsApp → Configuration → Webhook**
2. Set **Callback URL**: `https://yourdomain.com/webhooks/whatsapp`
3. Set **Verify Token**: the value of `WHATSAPP_VERIFY_TOKEN` in your `.env`
4. Click **Verify and Save**
5. Subscribe to the **messages** field

See `docs/whatsapp-setup.md` for the full setup guide.

---

## Step 9 — Configure Google OAuth (optional)

In Google Cloud Console, add this to **Authorized redirect URIs**:

```
https://yourdomain.com/google/callback
```

Update `.env`:
```env
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback
```

Then restart the app:
```bash
docker compose -f docker-compose.prod.yml restart app
```

---

## Useful Commands

```bash
# View all logs
docker compose -f docker-compose.prod.yml logs -f

# View logs for a specific service
docker compose -f docker-compose.prod.yml logs -f horizon

# Run artisan commands
docker compose -f docker-compose.prod.yml exec app php artisan <command>

# Run migrations manually
docker compose -f docker-compose.prod.yml exec app php artisan migrate

# Open a shell in the app container
docker compose -f docker-compose.prod.yml exec app sh

# Restart a service
docker compose -f docker-compose.prod.yml restart app

# Stop everything
docker compose -f docker-compose.prod.yml down

# Stop and remove volumes (DESTROYS ALL DATA)
docker compose -f docker-compose.prod.yml down -v
```

---

## Updating to a New Version

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build
```

Migrations run automatically on startup. Zero-downtime deploys are not yet configured — there will be a brief downtime (~30s) during rebuild.

---

## Backups

Backup the PostgreSQL database:
```bash
docker compose -f docker-compose.prod.yml exec postgres \
  pg_dump -U aipal aipal | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

Restore:
```bash
gunzip -c backup_YYYYMMDD_HHMMSS.sql.gz | \
  docker compose -f docker-compose.prod.yml exec -T postgres psql -U aipal aipal
```

---

## Firewall

Allow only necessary ports:
```bash
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP (Caddy redirects to HTTPS)
ufw allow 443/tcp   # HTTPS
ufw allow 443/udp   # HTTP/3
ufw enable
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| App shows 502 Bad Gateway | PHP-FPM not ready yet — wait 30s and check `docker logs app` |
| Certificate error on HTTPS | DNS not propagated yet, or port 80/443 blocked by firewall |
| Telegram webhook returns error | Verify HTTPS works first, then re-run `telegram:set-webhook` |
| WhatsApp webhook verification fails | Check `WHATSAPP_VERIFY_TOKEN` matches exactly in `.env` and Meta portal |
| Migrations fail on startup | Check DB credentials in `.env` and that postgres container is healthy |
| Emails not sending | Configure `MAIL_*` vars — use Resend, Mailgun, or SES for production |
