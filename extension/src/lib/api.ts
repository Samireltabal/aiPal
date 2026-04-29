import { loadConnection } from './storage';

export type CaptureKind = 'memory' | 'task' | 'note' | 'reminder';

export type CapturePayload = {
  kind: CaptureKind;
  url: string;
  title: string;
  prompt?: string;
  selection?: string;
  article?: string;
  remind_at?: string;
  context_id?: number | null;
};

export type ContextSummary = {
  id: number;
  name: string;
  kind: string;
  color: string;
  is_default: boolean;
};

export type PingResponse = {
  ok: true;
  user: { id: number; name: string; email: string };
  default_context: ContextSummary | null;
  app_version: string;
};

class ApiError extends Error {
  constructor(public status: number, message: string, public body?: unknown) {
    super(message);
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const conn = await loadConnection();
  if (!conn) throw new ApiError(0, 'Extension is not connected. Open the popup and connect first.');

  const res = await fetch(`${conn.host.replace(/\/$/, '')}${path}`, {
    ...init,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${conn.token}`,
      ...(init.headers ?? {}),
    },
  });

  const text = await res.text();
  let body: unknown = null;
  try {
    body = text ? JSON.parse(text) : null;
  } catch {
    /* leave body null */
  }

  if (!res.ok) {
    const message = (body && typeof body === 'object' && 'message' in body)
      ? String((body as { message: unknown }).message)
      : `Request failed (${res.status})`;
    throw new ApiError(res.status, message, body);
  }

  return body as T;
}

export async function ping(): Promise<PingResponse> {
  return request<PingResponse>('/api/v1/extension/ping');
}

export async function listContexts(): Promise<ContextSummary[]> {
  const data = await request<{ contexts: ContextSummary[] }>('/api/v1/extension/contexts');
  return data.contexts;
}

export async function capture(payload: CapturePayload): Promise<{ ok: true; id: number; kind: string; deep_link: string }> {
  return request('/api/v1/extension/capture', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function pingHost(host: string, token: string): Promise<PingResponse> {
  const res = await fetch(`${host.replace(/\/$/, '')}/api/v1/extension/ping`, {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  });
  const text = await res.text();
  if (!res.ok) throw new ApiError(res.status, `Ping failed (${res.status})`, text);
  return JSON.parse(text) as PingResponse;
}

export { ApiError };
