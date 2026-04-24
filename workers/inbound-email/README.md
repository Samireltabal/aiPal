# Cloudflare Email Worker — Forward-to-aiPal

Receives inbound mail on `inbound.<your-domain>`, extracts the recipient token,
parses the MIME body, HMAC-signs a JSON payload, and POSTs to aiPal.

## Prerequisites

- Domain on Cloudflare with Email Routing enabled.
- Wrangler CLI installed (`npm i -g wrangler`) and authenticated.
- A shared HMAC secret set on **both** the Worker (as `HMAC_SECRET`) and aiPal
  (`INBOUND_EMAIL_HMAC_SECRET` in `.env`).

## One-time setup

1. Copy `wrangler.example.toml` to `wrangler.toml` and edit the `name`, domain,
   and `AIPAL_ENDPOINT` (e.g. `https://samirai.xyz/webhooks/email/inbound`).
2. Put the HMAC secret:
   ```bash
   wrangler secret put HMAC_SECRET
   ```
3. Deploy:
   ```bash
   wrangler deploy
   ```
4. In Cloudflare dashboard → **Email** → **Email Routing**:
   - Add MX records for `inbound.<your-domain>` (Cloudflare wizard does this).
   - Create a **Catch-all** rule → action: "Send to a Worker" → select the Worker.

## Verifying

Send a test email to `forward-<token>@inbound.<your-domain>`. Check:
- Worker logs in the Cloudflare dashboard.
- aiPal `storage/logs/laravel.log` for the `InboundEmailController` entry.
- An inbox reply from aiPal with the confirmation line.

## Security notes

- The Worker HMAC-signs the exact request body with SHA-256. aiPal verifies with
  `hash_equals` (timing-safe).
- SPF/DKIM results are passed through as fields in the payload; aiPal rejects
  messages that fail either.
- Rotate the HMAC secret by updating both sides in quick succession — brief
  downtime is acceptable because inbound email is not time-critical.
