# Telegram Setup Guide

This guide walks you through connecting aiPal to Telegram.
Can be done locally or on a public server — Telegram only requires a reachable HTTPS URL for the webhook.

---

## Prerequisites

- A Telegram account
- A deployed aiPal instance reachable at a public HTTPS URL (e.g. `https://aipal.yourdomain.com`)
  - For local development you can use [ngrok](https://ngrok.com): `ngrok http 80`

---

## Step 1 — Create a Telegram Bot

1. Open Telegram and search for **@BotFather**
2. Send `/newbot`
3. Choose a display name (e.g. "My aiPal")
4. Choose a username ending in `bot` (e.g. `my_aipal_bot`)
5. BotFather replies with your **bot token** — copy it

---

## Step 2 — Configure Environment Variables

Add to your `.env`:

```env
TELEGRAM_BOT_TOKEN=1234567890:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TELEGRAM_WEBHOOK_SECRET=aipal-tg-<random-string>
```

Generate the webhook secret with any random string, e.g.:
```bash
openssl rand -hex 16
```

---

## Step 3 — Register the Webhook

Run the artisan command to register your webhook URL with Telegram:

```bash
php artisan telegram:set-webhook
```

Or manually via curl:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://aipal.yourdomain.com/webhooks/telegram" \
  -d "secret_token=<YOUR_WEBHOOK_SECRET>" \
  -d "allowed_updates=[\"message\"]"
```

Telegram will respond with `{"ok":true}` on success.

---

## Step 4 — Get Your Chat ID

1. Open Telegram and start a chat with your bot
2. Send `/start`
3. The bot replies with your **chat ID** — copy it

---

## Step 5 — Link Your Account in aiPal

1. Log into aiPal and go to **Settings → Telegram**
2. Paste your chat ID into the **Telegram Chat ID** field
3. Click **Save Telegram settings**

---

## Step 6 — Test It

Send any message to your bot. You should receive a reply from your aiPal assistant within a few seconds.

You can also send **voice notes** — they will be transcribed via Whisper and processed as text.

---

## Environment Variables Summary

```env
TELEGRAM_BOT_TOKEN=        # From @BotFather
TELEGRAM_WEBHOOK_SECRET=   # Any random string — used to verify incoming webhook requests
```

---

## Reminder Channel

When creating reminders in aiPal, set the channel to **telegram** to receive them via your bot.
Your chat ID must be linked in Settings for this to work.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Bot does not respond | Check that the webhook is registered — use `getWebhookInfo` API to verify |
| `401 Unauthorized` on webhook | `TELEGRAM_WEBHOOK_SECRET` in `.env` doesn't match what was registered with Telegram — re-run `telegram:set-webhook` |
| `/start` gives no reply | Bot token is wrong or the app is not reachable from the internet |
| Voice notes not transcribed | Ensure `OPENAI_API_KEY` is set — Whisper requires OpenAI |
| No reply after sending message | Check Horizon dashboard and `storage/logs/laravel.log` |

---

## Verify Webhook Status

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

Should show your URL, `has_custom_certificate: false`, and `pending_update_count: 0`.
