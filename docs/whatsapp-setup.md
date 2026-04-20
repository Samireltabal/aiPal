# WhatsApp Setup Guide (Meta Cloud API)

This guide walks you through connecting aiPal to WhatsApp via the Meta Cloud API.
Complete these steps **after** deploying to a public server with HTTPS.

---

## Prerequisites

- A Facebook / Meta account
- A deployed aiPal instance reachable at a public HTTPS URL (e.g. `https://aipal.yourdomain.com`)
- No business verification required for personal use

---

## Step 1 — Create a Meta Developer App

1. Go to [developers.facebook.com](https://developers.facebook.com) and log in
2. Click **My Apps → Create App**
3. Select app type: **Business**
4. Fill in app name (e.g. "aiPal") and contact email
5. Click **Create App**

---

## Step 2 — Add WhatsApp to Your App

1. On your app dashboard, click **Add Product**
2. Find **WhatsApp** and click **Set Up**
3. You'll land on the **WhatsApp → Getting Started** page

---

## Step 3 — Get Your Credentials

On the **Getting Started** page you'll find:

| Value | Where to find it |
|---|---|
| `WHATSAPP_ACCESS_TOKEN` | **Temporary access token** shown on the page (valid 24h) — for production, generate a permanent token (see Step 7) |
| `WHATSAPP_PHONE_NUMBER_ID` | Listed under **Phone Number ID** |

Copy both values into your `.env`:

```env
WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxxxxxxxx
WHATSAPP_PHONE_NUMBER_ID=123456789012345
```

---

## Step 4 — Add a Test Recipient (Your Personal Number)

On the **Getting Started** page:

1. Under **To**, click **Manage phone number list**
2. Add your personal WhatsApp number in international format (e.g. `+201234567890`)
3. You'll receive a confirmation code on WhatsApp — enter it

This whitelists your number to receive messages from the test bot.

---

## Step 5 — Configure the Webhook

1. In the left sidebar go to **WhatsApp → Configuration**
2. Under **Webhook**, click **Edit**
3. Set:
   - **Callback URL**: `https://aipal.yourdomain.com/webhooks/whatsapp`
   - **Verify Token**: any random string you choose (e.g. `aipal-wa-abc123`)
4. Add the same verify token to your `.env`:
   ```env
   WHATSAPP_VERIFY_TOKEN=aipal-wa-abc123
   ```
5. Click **Verify and Save** — Meta will send a GET request to your URL to confirm it responds correctly
6. After saving, under **Webhook fields** click **Manage** and subscribe to **messages**

---

## Step 6 — Add App Secret (Recommended)

The app secret lets aiPal verify that incoming webhook payloads are genuinely from Meta.

1. Go to **App Settings → Basic**
2. Copy the **App Secret**
3. Add to `.env`:
   ```env
   WHATSAPP_APP_SECRET=your_app_secret_here
   ```

If `WHATSAPP_APP_SECRET` is left empty, signature verification is skipped (fine for dev, not recommended for production).

---

## Step 7 — Generate a Permanent Access Token

The temporary token expires after 24 hours. For production:

1. Go to [business.facebook.com](https://business.facebook.com) → **Settings → System Users**
2. Create a **System User** with the role **Admin**
3. Click **Generate New Token** → select your app → grant `whatsapp_business_messaging` permission
4. Copy the token and update `WHATSAPP_ACCESS_TOKEN` in `.env`

---

## Step 8 — Link Your WhatsApp Number in aiPal

1. Log into aiPal and go to **Settings → WhatsApp**
2. Enter your WhatsApp number in international format **without the + sign**
   - Example: `201234567890` for Egypt (+20)
3. Click **Save WhatsApp settings**

Your number is now linked. Messages you send to the bot will route to your account.

---

## Step 9 — Test It

Send a message to the test phone number Meta provided (visible on the Getting Started page) from your whitelisted personal number.

You should receive a reply from your aiPal assistant within a few seconds.

---

## Environment Variables Summary

```env
# WhatsApp (Meta Cloud API)
WHATSAPP_ACCESS_TOKEN=        # From Meta Developer Portal (use permanent token for production)
WHATSAPP_PHONE_NUMBER_ID=     # Phone Number ID from Getting Started page
WHATSAPP_VERIFY_TOKEN=        # Any string you choose — must match what you enter in Meta webhook config
WHATSAPP_APP_SECRET=          # From App Settings → Basic (optional but recommended)
```

---

## Reminder Channel

When creating reminders in aiPal, set the channel to **whatsapp** to receive them on WhatsApp.
Your number must be linked in Settings for this to work.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Webhook verification fails | Make sure `WHATSAPP_VERIFY_TOKEN` in `.env` matches exactly what you entered in Meta portal |
| Messages not arriving | Check that your number is whitelisted (Step 4) and linked in Settings (Step 8) |
| `401 Unauthorized` on outbound messages | Access token has expired — generate a permanent system user token (Step 7) |
| No reply after sending message | Check Horizon dashboard and Laravel logs (`storage/logs/laravel.log`) |
| Signature verification fails | Double-check `WHATSAPP_APP_SECRET` matches the App Secret in Meta App Settings → Basic |
