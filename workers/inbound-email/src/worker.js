// Cloudflare Email Worker — Forward-to-aiPal
//
// Receives incoming email via Cloudflare Email Routing (catch-all rule on
// inbound.<domain>), extracts the text body, SHA-256/HMAC-signs a JSON
// payload, and POSTs to aiPal's /webhooks/email/inbound endpoint.
//
// env.AIPAL_ENDPOINT  — full URL
// env.HMAC_SECRET     — shared secret with aiPal (wrangler secret put)

const MAX_BODY_CHARS = 200_000; // ~200KB of text — aiPal caps again on its side.

export default {
  async email(message, env, ctx) {
    try {
      const rawBytes = await readAll(message.raw);
      const { text, html } = await extractText(rawBytes);

      const payload = {
        to: message.to,
        from: message.from,
        subject: message.headers.get("subject") || "",
        text: truncate(text || stripHtml(html || ""), MAX_BODY_CHARS),
        spf: verdict(message.headers.get("authentication-results"), "spf"),
        dkim: verdict(message.headers.get("authentication-results"), "dkim"),
        message_id: message.headers.get("message-id") || null,
        received_at: new Date().toISOString(),
      };

      const body = JSON.stringify(payload);
      const signature = await hmacSha256Hex(env.HMAC_SECRET, body);

      const resp = await fetch(env.AIPAL_ENDPOINT, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Inbound-Signature": signature,
          "User-Agent": "aipal-inbound-email-worker/1",
        },
        body,
      });

      if (!resp.ok) {
        const txt = await resp.text();
        console.error("aiPal rejected:", resp.status, txt);
        // Reject the email so senders get a bounce rather than a silent drop.
        message.setReject(`aiPal rejected with ${resp.status}`);
      }
    } catch (err) {
      console.error("worker error:", err?.message || err);
      message.setReject("processing error");
    }
  },
};

async function readAll(stream) {
  const reader = stream.getReader();
  const chunks = [];
  let total = 0;
  while (true) {
    const { value, done } = await reader.read();
    if (done) break;
    chunks.push(value);
    total += value.length;
  }
  const out = new Uint8Array(total);
  let offset = 0;
  for (const c of chunks) {
    out.set(c, offset);
    offset += c.length;
  }
  return out;
}

// Very lean MIME text extractor: pulls the first text/plain (or text/html) part.
// For a v1 feature, this covers the >95% case. Complex multipart / base64 /
// quoted-printable edge cases fall back to raw string.
async function extractText(bytes) {
  const raw = new TextDecoder("utf-8", { fatal: false }).decode(bytes);

  const boundaryMatch = raw.match(/boundary="?([^";\r\n]+)"?/i);
  if (!boundaryMatch) {
    return { text: stripHeaders(raw), html: null };
  }

  const boundary = `--${boundaryMatch[1]}`;
  const parts = raw.split(boundary);
  let text = null;
  let html = null;

  for (const part of parts) {
    const headerEnd = part.indexOf("\r\n\r\n");
    if (headerEnd === -1) continue;
    const headers = part.slice(0, headerEnd).toLowerCase();
    let body = part.slice(headerEnd + 4);

    if (headers.includes("content-transfer-encoding: quoted-printable")) {
      body = decodeQuotedPrintable(body);
    } else if (headers.includes("content-transfer-encoding: base64")) {
      body = decodeBase64(body);
    }

    if (headers.includes("content-type: text/plain") && !text) {
      text = body.trim();
    } else if (headers.includes("content-type: text/html") && !html) {
      html = body.trim();
    }
  }

  return { text, html };
}

function stripHeaders(raw) {
  const idx = raw.indexOf("\r\n\r\n");
  return idx === -1 ? raw : raw.slice(idx + 4);
}

function stripHtml(html) {
  return html
    .replace(/<style[\s\S]*?<\/style>/gi, "")
    .replace(/<script[\s\S]*?<\/script>/gi, "")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function decodeQuotedPrintable(s) {
  return s
    .replace(/=\r?\n/g, "")
    .replace(/=([0-9A-F]{2})/gi, (_, h) => String.fromCharCode(parseInt(h, 16)));
}

function decodeBase64(s) {
  try {
    return atob(s.replace(/\s+/g, ""));
  } catch {
    return s;
  }
}

function verdict(authResults, method) {
  if (!authResults) return "none";
  const m = authResults.match(new RegExp(`${method}=(\\w+)`, "i"));
  return m ? m[1].toLowerCase() : "none";
}

function truncate(s, n) {
  if (!s) return "";
  return s.length > n ? s.slice(0, n) : s;
}

async function hmacSha256Hex(secret, body) {
  const key = await crypto.subtle.importKey(
    "raw",
    new TextEncoder().encode(secret),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );
  const sig = await crypto.subtle.sign("HMAC", key, new TextEncoder().encode(body));
  return Array.from(new Uint8Array(sig))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}
