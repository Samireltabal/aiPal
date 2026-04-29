/**
 * Background service worker.
 *
 * Responsibilities:
 *  - register & handle right-click context menu items
 *  - extract page content from the active tab on demand (executeScript)
 *  - relay extraction results to the popup
 *
 * Stays stateless: MV3 service workers die after ~30s idle. All persistent
 * state lives in chrome.storage via lib/storage.ts.
 */

import { extractFromPage, type Extracted } from '../content/extractor';
import { capture } from '../lib/api';

const MENU = {
  ASK: 'aipal-ask',
  ASK_SELECTION: 'aipal-ask-selection',
  SAVE_MEMORY: 'aipal-save-memory',
  CREATE_TASK: 'aipal-create-task',
} as const;

chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.removeAll(() => {
    chrome.contextMenus.create({ id: MENU.ASK, title: 'Ask aiPal about this page', contexts: ['page'] });
    chrome.contextMenus.create({ id: MENU.ASK_SELECTION, title: 'Ask aiPal about selection', contexts: ['selection'] });
    chrome.contextMenus.create({ id: MENU.SAVE_MEMORY, title: 'Save to aiPal memory', contexts: ['page', 'selection'] });
    chrome.contextMenus.create({ id: MENU.CREATE_TASK, title: 'Create aiPal task from page', contexts: ['page'] });
  });
});

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  if (!tab?.id) return;
  const data = await extractInTab(tab.id);
  if (!data) return;

  switch (info.menuItemId) {
    case MENU.ASK:
    case MENU.ASK_SELECTION:
      await openChatWithPrefill(data);
      return;
    case MENU.SAVE_MEMORY:
      await capture({ kind: 'memory', url: data.url, title: data.title, selection: data.selection ?? undefined });
      notify('Saved to aiPal memory.');
      return;
    case MENU.CREATE_TASK:
      await capture({ kind: 'task', url: data.url, title: data.title, prompt: data.title });
      notify('Task created in aiPal.');
      return;
  }
});

// Popup asks background to do the extraction so it has the right tab context
// even when the popup itself triggered the call.
chrome.runtime.onMessage.addListener((msg, _sender, sendResponse) => {
  if (msg?.type === 'extract-active-tab') {
    (async () => {
      const [tab] = await chrome.tabs.query({ active: true, lastFocusedWindow: true });
      const data = tab?.id ? await extractInTab(tab.id) : null;
      sendResponse(data);
    })();
    return true; // keep the channel open for async sendResponse
  }
  return false;
});

async function extractInTab(tabId: number): Promise<Extracted | null> {
  try {
    const [{ result } = { result: null }] = await chrome.scripting.executeScript({
      target: { tabId },
      func: extractFromPage,
    });
    return (result as Extracted | null) ?? null;
  } catch (e) {
    console.warn('[aiPal] extract failed', e);
    return null;
  }
}

async function openChatWithPrefill(data: Extracted): Promise<void> {
  const { loadConnection } = await import('../lib/storage');
  const conn = await loadConnection();
  if (!conn) return;

  const message = [
    data.selection ? `> ${data.selection}` : '',
    `(from ${data.title} — ${data.url})`,
  ].filter(Boolean).join('\n\n');

  const url = `${conn.host.replace(/\/$/, '')}/chat?prefill=${encodeURIComponent(message)}`;
  await chrome.tabs.create({ url });
}

function notify(message: string): void {
  // Service workers can't show transient toasts directly; use the badge as a quick
  // visual ack. Popup will surface its own toast for popup-initiated captures.
  chrome.action.setBadgeBackgroundColor({ color: '#10b981' });
  chrome.action.setBadgeText({ text: '✓' });
  chrome.action.setTitle({ title: `aiPal — ${message}` });
  setTimeout(() => {
    chrome.action.setBadgeText({ text: '' });
    chrome.action.setTitle({ title: 'aiPal' });
  }, 3000);
}
