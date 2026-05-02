# Browser Extension Setup Guide

aiPal provides a Chrome/Chromium browser extension that lets you capture pages, selections, tasks, notes, and reminders from any tab directly into your self-hosted aiPal instance.

> **Full development reference:** See [`extension/README.md`](../extension/README.md) for detailed build instructions, permissions, and developer notes.

---

## Features

- **Ask** — Opens aiPal chat in a new tab pre-filled with the current page as context
- **Memory** — Saves the current page (or your selection) as a long-term memory (RAG)
- **Task** — Creates a task; if you set a remind-at time it becomes a reminder
- **Note** — Saves the page text as a note
- **Right-click context menu** — One-click capture without opening the popup
- **Keyboard shortcut** — `Cmd+Shift+A` (macOS) / `Ctrl+Shift+A` (Windows/Linux)

---

## Prerequisites

- A running aiPal instance (self-hosted, with HTTPS enabled)
- Google Chrome or a Chromium-based browser (Edge, Brave, Vivaldi, Opera)
- An **extension token** generated from your aiPal dashboard

---

## Step 1: Generate an Extension Token

1. Log in to your aiPal instance as an admin user.
2. Go to **Settings → Browser Extension**.
3. Click **Generate Token**.
4. Copy the token string — you will need it in Step 3.

> Tokens are single-use and tied to your user account. You can revoke them at any time from the same settings page.

---

## Step 2: Install the Extension

### Option A — Install from a Release (Recommended)

1. Download the latest `aipal-extension-v*.zip` from the [Releases page](https://github.com/Samireltabal/aiPal/releases).
2. Unzip the archive to a folder on your machine.
3. Open `chrome://extensions` in your browser.
4. Enable **Developer mode** (toggle in the top-right corner).
5. Click **Load unpacked** and select the unzipped folder.

### Option B — Build from Source

If you want the latest changes or plan to modify the extension:

```bash
cd extension
npm install
npm run build
```

Then follow the same steps as Option A, pointing to the `extension/dist` folder.

---

## Step 3: Configure the Extension

1. Click the puzzle-piece (extensions) icon in your browser toolbar.
2. Pin the **aiPal** extension for easy access.
3. Click the aiPal icon and then the gear icon ⚙️ (or right-click → **Options**).
4. Enter:
   - **Host URL** — Your aiPal instance URL, e.g. `https://aipal.example.com`
   - **Token** — The token you generated in Step 1
5. (Optional) Uncheck **Sync this connection** if you prefer to keep the token only on the current device.
6. Click **Save**.

---

## Step 4: Use the Extension

Once configured, you can:

- **Capture the current page:** Click the aiPal icon in the toolbar and choose **Memory**, **Task**, or **Note**.
- **Capture a selection:** Select text on any page, right-click, and choose **Save to aiPal → Memory / Task / Note**.
- **Quick ask:** Choose **Ask aiPal** from the popup or right-click menu to open a chat pre-populated with page context.

---

## Troubleshooting

| Problem | Solution |
|---|---|
| "Connection failed" error | Verify your Host URL is correct and reachable (including `https://`). |
| "Invalid token" error | Regenerate the token in **Settings → Browser Extension** and update the extension options. |
| Extension icon is greyed out | Ensure your aiPal instance is running and HTTPS is properly configured. |
| Right-click menu doesn't appear | Reload the extension from `chrome://extensions` (click the refresh icon on the aiPal card). |
| Token not saving | Check that `chrome.storage` is enabled. If "Sync this connection" is unchecked, the token is stored locally only. |

For more help, see the [Troubleshooting Guide](./troubleshooting.md) or open a [GitHub issue](https://github.com/Samireltabal/aiPal/issues).

---

## Security Notes

- The extension requests **no broad host permissions** — it can only read the active tab when you explicitly click the icon, use the context menu, or press the keyboard shortcut.
- Your host URL and token are stored in `chrome.storage.sync` (synced across Chrome sign-in) by default. Uncheck **Sync this connection** for device-local storage only.
- Revoke unused tokens from **Settings → Browser Extension** in aiPal.

---

## Limitations (MVP)

- Chrome/Chromium only (Firefox MV3 support is planned but not yet tested).
- No inline overlay on third-party sites (e.g. Gmail sidebar) — coming in a future version.
- Page text extraction uses `<article>` / `<main>` / `<body>` in that order; Mozilla Readability is not yet bundled.

---

## See Also

- [Extension README (Development)](../extension/README.md) — Full developer reference, build commands, and technical details.
- [Google OAuth Setup](./google-oauth-setup.md) — If you want the extension to interact with Gmail or Calendar.
