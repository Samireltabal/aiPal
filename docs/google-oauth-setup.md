# Google OAuth Setup Guide

This guide walks you through creating a Google Cloud project and generating OAuth 2.0 credentials so aiPal can connect to Google Calendar.

---

## Prerequisites

- A Google account
- Access to [Google Cloud Console](https://console.cloud.google.com/)

---

## Step 1 — Create a Google Cloud Project

1. Go to [console.cloud.google.com](https://console.cloud.google.com/).
2. Click the project selector at the top → **New Project**.
3. Enter a project name (e.g. `aipal`) and click **Create**.
4. Wait a few seconds for the project to be created, then select it.

---

## Step 2 — Enable the Google Calendar API

1. In the left sidebar, go to **APIs & Services → Library**.
2. Search for **Google Calendar API**.
3. Click the result, then click **Enable**.

---

## Step 3 — Configure the OAuth Consent Screen

1. Go to **APIs & Services → OAuth consent screen**.
2. Choose **External** (for personal use) and click **Create**.
3. Fill in the required fields:
   - **App name**: aiPal
   - **User support email**: your email address
   - **Developer contact email**: your email address
4. Click **Save and Continue** through the Scopes and Test Users steps.
   - On the **Scopes** step, click **Add or Remove Scopes** and add:
     - `https://www.googleapis.com/auth/calendar.readonly`
5. On the **Test Users** step, add your own Google account email so you can log in during development.
6. Click **Save and Continue**, then **Back to Dashboard**.

> **Note:** While the app is in "Testing" mode, only accounts added as test users can authorize it. To make it available to others, you would need to publish the app (which requires a Google verification review).

---

## Step 4 — Create OAuth 2.0 Credentials

1. Go to **APIs & Services → Credentials**.
2. Click **Create Credentials → OAuth client ID**.
3. Choose **Web application** as the application type.
4. Set the name to `aiPal Web Client` (or any name you prefer).
5. Under **Authorized redirect URIs**, add:
   ```
   http://localhost/google/callback
   ```
   Replace `localhost` with your actual domain if deploying to production (e.g. `https://aipal.example.com/google/callback`).
6. Click **Create**.

Google will display your **Client ID** and **Client Secret**. Copy them now — the secret is shown only once.

---

## Step 5 — Add Credentials to Your `.env`

Open your `.env` file and add:

```env
GOOGLE_CLIENT_ID=your-client-id-here
GOOGLE_CLIENT_SECRET=your-client-secret-here
GOOGLE_REDIRECT_URI=http://localhost/google/callback
```

Update `GOOGLE_REDIRECT_URI` to match the redirect URI you registered in Step 4.

---

## Step 6 — Connect Your Account

1. Start aiPal and log in.
2. Go to **Settings → Daily Briefing**.
3. Click **Connect Google**.
4. You will be redirected to Google's authorization page.
5. Select your Google account and grant the calendar read permission.
6. You'll be redirected back to Settings with a "Google Calendar connected" confirmation.

---

## Troubleshooting

| Problem | Solution |
|---|---|
| `redirect_uri_mismatch` error | Ensure the URI in your `.env` exactly matches what you registered in Google Cloud Console (including `http` vs `https`). |
| `Access blocked: app not verified` | Add your Google account as a test user in the OAuth consent screen settings. |
| Token expired / calendar not loading | Disconnect and reconnect your Google account from Settings. The refresh token flow will handle expiry automatically after the first connection. |
| `invalid_grant` after reconnecting | Revoke old access at [myaccount.google.com/permissions](https://myaccount.google.com/permissions) and reconnect. |

---

## Production Deployment Notes

- Use `https://` for your redirect URI in production — Google does not allow plain HTTP for published apps.
- If you want users other than yourself to connect Google accounts, you must submit the app for Google verification. For a self-hosted personal tool, keeping it in "Testing" mode with yourself as the only test user is perfectly fine.
- Never commit your `GOOGLE_CLIENT_SECRET` to version control.
