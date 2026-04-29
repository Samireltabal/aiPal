import { pingHost } from '../lib/api';
import { clearConnection, loadConnection, saveConnection } from '../lib/storage';

const $ = <T extends HTMLElement>(id: string) => document.getElementById(id) as T;

const form = $<HTMLFormElement>('form');
const hostInput = $<HTMLInputElement>('host');
const tokenInput = $<HTMLInputElement>('token');
const syncInput = $<HTMLInputElement>('syncStorage');
const status = $<HTMLDivElement>('status');
const connectedPanel = $<HTMLDivElement>('connected');
const connectedHost = $<HTMLSpanElement>('connected-host');
const connectedUser = $<HTMLSpanElement>('connected-user');

async function refresh(): Promise<void> {
  const conn = await loadConnection();
  if (conn) {
    connectedHost.textContent = conn.host;
    connectedUser.textContent = '…';
    connectedPanel.classList.remove('hidden');
    try {
      const ping = await pingHost(conn.host, conn.token);
      connectedUser.textContent = ping.user.email;
    } catch {
      connectedUser.textContent = '(token may be invalid)';
    }
  } else {
    connectedPanel.classList.add('hidden');
  }
}

form.addEventListener('submit', async e => {
  e.preventDefault();
  status.className = '';
  status.textContent = 'Connecting…';

  const host = hostInput.value.trim().replace(/\/$/, '');
  const token = tokenInput.value.trim();

  try {
    const ping = await pingHost(host, token);
    await saveConnection({
      host,
      token,
      defaultContextId: ping.default_context?.id ?? null,
      storage: syncInput.checked ? 'sync' : 'local',
    });
    status.className = 'ok';
    status.textContent = `Connected as ${ping.user.email}.`;
    tokenInput.value = '';
    await refresh();
  } catch (err) {
    status.className = 'err';
    status.textContent = err instanceof Error ? err.message : 'Connection failed.';
  }
});

document.getElementById('disconnect')?.addEventListener('click', async () => {
  await clearConnection();
  status.className = 'ok';
  status.textContent = 'Disconnected.';
  await refresh();
});

refresh();
