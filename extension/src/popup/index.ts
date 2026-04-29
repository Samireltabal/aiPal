import {
  capture,
  listContexts,
  ping,
  type CaptureKind,
  type ContextSummary,
} from '../lib/api';
import { extractFromPage, type Extracted } from '../content/extractor';
import { loadConnection } from '../lib/storage';

type Tab = 'ask' | 'memory' | 'task' | 'note';

type State = {
  connected: boolean;
  loading: boolean;
  tab: Tab;
  pageTitle: string;
  pageUrl: string;
  selection: string;
  article: string;
  prompt: string;
  remindAt: string;
  useSelectionOnly: boolean;
  contexts: ContextSummary[];
  contextId: number | null;
  toast: { kind: 'ok' | 'err'; text: string } | null;
};

const state: State = {
  connected: false,
  loading: true,
  tab: 'ask',
  pageTitle: '',
  pageUrl: '',
  selection: '',
  article: '',
  prompt: '',
  remindAt: '',
  useSelectionOnly: false,
  contexts: [],
  contextId: null,
  toast: null,
};

const root = document.getElementById('root')!;

function escape(s: string): string {
  return s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]!));
}

function render(): void {
  if (state.loading) {
    root.innerHTML = '<div class="empty">Loading…</div>';
    return;
  }

  if (!state.connected) {
    root.innerHTML = `
      <div class="empty">
        <div>aiPal is not connected.</div>
        <button id="open-options">Connect now</button>
      </div>
    `;
    document.getElementById('open-options')!.addEventListener('click', () => chrome.runtime.openOptionsPage());
    return;
  }

  const ctx = state.contexts.find(c => c.id === state.contextId);

  const noPage = !state.pageUrl || !/^https?:/.test(state.pageUrl);

  root.innerHTML = `
    <div class="header">
      <strong>aiPal</strong>
      <span class="ctx">${ctx ? `→ ${escape(ctx.name)}` : 'no context'}</span>
    </div>
    ${noPage ? `<div class="toast err">aiPal can't capture from internal pages (chrome://, new tab, store). Open a regular website first.</div>` : ''}
    <div class="tabs">
      ${(['ask', 'memory', 'task', 'note'] as Tab[])
        .map(t => `<button data-tab="${t}" class="${state.tab === t ? 'active' : ''}">${labelFor(t)}</button>`)
        .join('')}
    </div>
    <div class="body">
      <div class="page-meta">
        <strong>${escape(state.pageTitle || 'Current page')}</strong>
        ${escape(state.pageUrl)}
      </div>
      ${state.selection ? `
        <label class="checkbox">
          <input type="checkbox" id="sel-only" ${state.useSelectionOnly ? 'checked' : ''} />
          Use selected text only (${state.selection.length} chars)
        </label>` : ''}
      <div class="field">
        <label for="prompt">${promptLabel()}</label>
        <textarea id="prompt" placeholder="${promptPlaceholder()}">${escape(state.prompt)}</textarea>
      </div>
      ${state.tab === 'task' ? `
        <div class="field">
          <label for="remind">Remind at (optional, switches to reminder)</label>
          <input id="remind" type="datetime-local" value="${escape(state.remindAt)}" />
        </div>` : ''}
      <div class="field">
        <label for="ctx">Context</label>
        <select id="ctx">
          ${state.contexts.map(c =>
            `<option value="${c.id}" ${c.id === state.contextId ? 'selected' : ''}>${escape(c.name)}${c.is_default ? ' (default)' : ''}</option>`,
          ).join('')}
        </select>
      </div>
    </div>
    ${state.toast ? `<div class="toast ${state.toast.kind}">${escape(state.toast.text)}</div>` : ''}
    <div class="actions">
      <button id="send">${actionLabel()}</button>
    </div>
  `;

  document.querySelectorAll<HTMLButtonElement>('[data-tab]').forEach(btn => {
    btn.addEventListener('click', () => { state.tab = btn.dataset.tab as Tab; render(); });
  });
  document.getElementById('prompt')!.addEventListener('input', e => {
    state.prompt = (e.target as HTMLTextAreaElement).value;
  });
  document.getElementById('ctx')!.addEventListener('change', e => {
    state.contextId = Number((e.target as HTMLSelectElement).value);
  });
  const remind = document.getElementById('remind') as HTMLInputElement | null;
  remind?.addEventListener('input', e => { state.remindAt = (e.target as HTMLInputElement).value; });
  const selOnly = document.getElementById('sel-only') as HTMLInputElement | null;
  selOnly?.addEventListener('change', e => { state.useSelectionOnly = (e.target as HTMLInputElement).checked; });
  document.getElementById('send')!.addEventListener('click', send);
}

function labelFor(t: Tab): string {
  return ({ ask: 'Ask', memory: 'Memory', task: 'Task', note: 'Note' })[t];
}
function promptLabel(): string {
  return ({
    ask: 'Question',
    memory: 'Note (optional)',
    task: 'Task title',
    note: 'Title',
  })[state.tab];
}
function promptPlaceholder(): string {
  return ({
    ask: 'Ask aiPal about this page…',
    memory: 'Why are you saving this?',
    task: 'Read this later',
    note: 'Note title',
  })[state.tab];
}
function actionLabel(): string {
  if (state.tab === 'ask') return 'Open chat with this page';
  if (state.tab === 'task' && state.remindAt) return 'Save reminder';
  return `Save ${state.tab}`;
}

async function send(): Promise<void> {
  const btn = document.getElementById('send') as HTMLButtonElement;
  btn.disabled = true;

  try {
    if (state.tab === 'ask') {
      await openChatWithPrefill();
      window.close();
      return;
    }

    const kind: CaptureKind = state.tab === 'task' && state.remindAt ? 'reminder' : (state.tab as CaptureKind);
    const selection = state.useSelectionOnly && state.selection ? state.selection : undefined;
    const article = state.useSelectionOnly ? undefined : (state.article || undefined);

    const result = await capture({
      kind,
      url: state.pageUrl,
      title: state.pageTitle || state.pageUrl,
      prompt: state.prompt || undefined,
      selection,
      article,
      remind_at: state.remindAt ? new Date(state.remindAt).toISOString() : undefined,
      context_id: state.contextId ?? undefined,
    });

    state.toast = { kind: 'ok', text: `Saved (${result.kind}).` };
    state.prompt = '';
    state.remindAt = '';
    render();
    setTimeout(() => window.close(), 900);
  } catch (e) {
    state.toast = { kind: 'err', text: e instanceof Error ? e.message : String(e) };
    render();
  } finally {
    btn.disabled = false;
  }
}

async function openChatWithPrefill(): Promise<void> {
  const conn = await loadConnection();
  if (!conn) return;

  const message = [
    state.prompt,
    state.useSelectionOnly && state.selection ? `> ${state.selection}` : '',
    `(from ${state.pageTitle} — ${state.pageUrl})`,
  ].filter(Boolean).join('\n\n');

  const url = `${conn.host.replace(/\/$/, '')}/chat?prefill=${encodeURIComponent(message)}`;
  await chrome.tabs.create({ url });
}

async function init(): Promise<void> {
  const conn = await loadConnection();
  if (!conn) {
    state.connected = false;
    state.loading = false;
    render();
    return;
  }

  try {
    const [pingResp, contexts] = await Promise.all([ping(), listContexts()]);
    state.connected = true;
    state.contexts = contexts;
    state.contextId = pingResp.default_context?.id ?? contexts[0]?.id ?? null;
  } catch (e) {
    state.toast = { kind: 'err', text: e instanceof Error ? e.message : 'Connection failed' };
    state.connected = false;
    state.loading = false;
    render();
    return;
  }

  // Query the active tab directly — popup has activeTab grant from the user click.
  // Use tab.url/title as guaranteed fallbacks; executeScript only adds selection/article.
  try {
    const [tab] = await chrome.tabs.query({ active: true, lastFocusedWindow: true });
    if (tab?.url) {
      state.pageUrl = tab.url;
      state.pageTitle = tab.title ?? tab.url;
    }
    if (tab?.id && tab.url && /^https?:/.test(tab.url)) {
      const [{ result } = { result: null }] = await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: extractFromPage,
      });
      const extracted = result as Extracted | null;
      if (extracted) {
        state.pageTitle = extracted.title || state.pageTitle;
        state.pageUrl = extracted.url || state.pageUrl;
        state.selection = extracted.selection ?? '';
        state.article = extracted.article ?? '';
        state.useSelectionOnly = !!extracted.selection;
      }
    }
  } catch (e) {
    console.warn('[aiPal] extract failed', e);
  }

  state.loading = false;
  render();
}

init();
