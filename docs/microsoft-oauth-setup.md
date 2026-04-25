# Microsoft OAuth Setup Guide

This guide walks you through registering an Azure AD application so your
aiPal deployment can connect to Outlook (mail) and Microsoft Calendar via
Microsoft Graph.

> Each aiPal deployment registers its own Azure app. There is no shared
> multi-tenant app — you own your client secret.

---

## Prerequisites

- A Microsoft account (personal *or* work/school — both work)
- Access to the [Azure Portal](https://portal.azure.com/)
- Your aiPal deployment's public HTTPS URL (e.g. `https://app.example.com`)
  - For local dev: `http://localhost:8000` is allowed for redirect URIs

---

## Step 1 — Open Microsoft Entra ID (Azure AD)

1. Go to [portal.azure.com](https://portal.azure.com/) and sign in.
2. In the top search bar, search for **Microsoft Entra ID** and open it.
   (This is the new name for Azure Active Directory — older docs may
   still call it "Azure AD".)

---

## Step 2 — Register the application

1. In the left sidebar, click **App registrations**.
2. Click **+ New registration** at the top.
3. Fill in:
   - **Name**: `aiPal` (or whatever you like — users will see this on the
     consent screen)
   - **Supported account types**: select
     **Accounts in any organizational directory and personal Microsoft accounts**
     (the third option). This corresponds to the `/common` authority and
     lets both work and personal accounts connect.
   - **Redirect URI**:
     - Platform: **Web**
     - URL: `https://YOUR-DOMAIN/auth/microsoft/callback`
       (for local dev: `http://localhost:8000/auth/microsoft/callback`)
4. Click **Register**.

You're now on the app's **Overview** page. Keep this tab open — you'll
copy two values from here.

---

## Step 3 — Copy the Application (client) ID

On the **Overview** page, copy:

- **Application (client) ID** → this becomes `MS_GRAPH_CLIENT_ID`

Tenant ID is *not* needed when using `/common` authority — leave
`MS_GRAPH_TENANT=common` in `.env`.

---

## Step 4 — Create a client secret

1. In the left sidebar of your app registration, click
   **Certificates & secrets**.
2. Under the **Client secrets** tab, click **+ New client secret**.
3. Description: `aiPal production` (or `aiPal local dev`).
4. Expiry: pick a duration. **24 months** is the longest Azure allows.
   Set a calendar reminder for rotation.
5. Click **Add**.
6. Copy the **Value** column **immediately** — Azure will hide it after
   you navigate away. This becomes `MS_GRAPH_CLIENT_SECRET`.

> If you miss the value, you can't recover it. Delete the secret and
> create a new one.

---

## Step 5 — Configure API permissions

1. In the left sidebar, click **API permissions**.
2. Click **+ Add a permission** → **Microsoft Graph** → **Delegated
   permissions**.
3. Search for and check each of these:
   - `offline_access` (under "OpenId permissions")
   - `openid`
   - `email`
   - `profile`
   - `Mail.Read`
   - `Calendars.ReadWrite`
4. Click **Add permissions**.

> **Do NOT add `Mail.Send`.** aiPal's Microsoft integration is read-only
> for mail by design.

You do not need to grant admin consent for personal accounts. For work
accounts, the user (or their tenant admin) grants consent the first time
they connect via aiPal.

---

## Step 6 — Add additional redirect URIs (optional)

If you run aiPal in multiple environments (local + staging + prod),
register all the redirect URIs under **Authentication** → **Web** →
**Redirect URIs**. Each environment will read its own `.env` value.

Make sure **Allow public client flows** is set to **No** — aiPal uses the
confidential-client (web) flow, not a public-client flow.

---

## Step 7 — Add the values to your aiPal `.env`

```env
MS_GRAPH_CLIENT_ID=<Application (client) ID from Step 3>
MS_GRAPH_CLIENT_SECRET=<Client secret value from Step 4>
MS_GRAPH_REDIRECT_URI=https://YOUR-DOMAIN/auth/microsoft/callback
MS_GRAPH_TENANT=common
```

For local dev:

```env
MS_GRAPH_REDIRECT_URI=http://localhost:8000/auth/microsoft/callback
```

After editing `.env`, restart the queue/web workers so Laravel picks up
the new values.

---

## Step 8 — Connect from aiPal

1. Open aiPal's **Settings** page.
2. Find the **Microsoft accounts** card.
3. Click **Connect Microsoft account**.
4. Sign in with your Microsoft account.
5. Approve the requested permissions on the consent screen.
6. You'll be redirected back to aiPal with the account connected.

You can connect multiple Microsoft accounts (e.g. one personal + one
work). Each lives as its own connection and can be assigned to a
different context.

---

## Troubleshooting

**"AADSTS50011: The redirect URI specified in the request does not match"**
The URI in your `.env` must match a URI registered under
**Authentication** → **Web** → **Redirect URIs** *exactly* — including
scheme, host, port, and path. `http` vs `https` and trailing slashes
matter.

**"AADSTS65001: The user or administrator has not consented"**
For work/school tenants, the tenant admin may need to approve the app.
Either ask your admin, or test with a personal Microsoft account first.

**"invalid_client" on token exchange**
Your `MS_GRAPH_CLIENT_SECRET` is wrong, expired, or you copied the
secret ID instead of the secret value. Generate a new one (Step 4) and
update `.env`.

**Token expired after some time**
Long-lived sessions rely on the refresh token. Make sure
`offline_access` is in your scopes (Step 5) — without it, the connection
will silently break after ~1 hour.

---

## Secret rotation

Set a reminder for ~1 month before your client secret expires:

1. Go to **Certificates & secrets**.
2. Create a new secret (Step 4).
3. Update `.env` and restart workers.
4. Once you've confirmed the new secret works, delete the old one.

aiPal will re-use existing connections — users do **not** need to
re-authenticate when you rotate the secret.
