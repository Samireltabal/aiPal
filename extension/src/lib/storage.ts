/**
 * Wrapper around chrome.storage.sync for the connection settings.
 * sync = follows user across machines they're signed into Chrome on.
 * For a personal self-hosted assistant this is acceptable; users running
 * on shared profiles should use the per-machine `local` mode (toggle in options).
 */

export type Connection = {
  host: string;
  token: string;
  defaultContextId: number | null;
  storage: 'sync' | 'local';
};

const KEY = 'connection';

export async function loadConnection(): Promise<Connection | null> {
  const fromSync = await chrome.storage.sync.get(KEY);
  if (fromSync[KEY]) return fromSync[KEY] as Connection;
  const fromLocal = await chrome.storage.local.get(KEY);
  return (fromLocal[KEY] as Connection | undefined) ?? null;
}

export async function saveConnection(c: Connection): Promise<void> {
  // Wipe whichever bucket we're not using to avoid stale token leaks.
  if (c.storage === 'sync') {
    await chrome.storage.local.remove(KEY);
    await chrome.storage.sync.set({ [KEY]: c });
  } else {
    await chrome.storage.sync.remove(KEY);
    await chrome.storage.local.set({ [KEY]: c });
  }
}

export async function clearConnection(): Promise<void> {
  await chrome.storage.sync.remove(KEY);
  await chrome.storage.local.remove(KEY);
}
