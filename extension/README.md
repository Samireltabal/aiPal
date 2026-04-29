# aiPal Browser Extension

Capture pages, selections, tasks, notes, and reminders from any tab into your
self-hosted aiPal instance.

## What it does

- **Ask** — opens aiPal chat in a new tab pre-filled with the current page as context
- **Memory** — saves the page (or your selection) as a long-term memory
- **Task** — creates a task; if you set a remind-at time it becomes a reminder
- **Note** — saves the page text as a note
- Right-click context menu items for one-click capture
- Keyboard shortcut: `Cmd+Shift+A` (macOS) / `Ctrl+Shift+A` (Win/Linux)

## Install (unpacked, for development)

1. **Build:**
   ```bash
   cd extension
   npm install
   npm run build
   ```
2. Open `chrome://extensions`, enable **Developer mode**, click **Load unpacked**, pick the `extension/dist` directory.
3. Open the extension's **Options** (puzzle-piece menu → aiPal → Options) and paste:
   - your aiPal host URL (e.g. `https://aipal.example.com`)
   - an extension token, generated at **Settings → Browser Extension** in aiPal

## Install from a release zip

Download `aipal-extension-v*.zip` from the [Releases](https://github.com/Samireltabal/aiPal/releases),
unzip, then *Load unpacked* and pick the unzipped folder.

## Permissions

Minimal: `activeTab`, `contextMenus`, `storage`, `scripting`. The extension
requests **no** broad host permissions — page extraction runs only on your
explicit gesture (popup open, context-menu click, or shortcut).

## Token storage

By default, your host + token live in `chrome.storage.sync` and follow you
across machines you're signed into Chrome on. If you'd rather keep the token
on this device only, uncheck **Sync this connection** on the Options page.

## Limitations (MVP)

- Chrome (and Chromium-derivatives) only. Firefox MV3 should work with minor
  manifest tweaks; not tested.
- No content-script overlays on third-party sites yet (e.g. inline Gmail
  sidebar). Coming in v2.
- Mozilla Readability article extraction is not yet bundled — page text is
  scraped from `<article>` / `<main>` / `<body>` in that order.

## Development

```bash
npm run dev    # Vite + crxjs HMR
npm run build  # production build to ./dist
npm run zip    # build then create ./aipal-extension.zip
```
