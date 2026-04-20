# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅ Yes    |

---

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

To report a vulnerability, email **samir.m.eltabal@gmail.com** with:

- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Any suggested fix (optional)

You will receive an acknowledgement within **48 hours** and a status update within **7 days**.

---

## Disclosure Policy

- Vulnerabilities will be fixed in a private branch and released as a patch version
- A security advisory will be published on GitHub after the fix is released
- Credit will be given to the reporter unless they prefer to remain anonymous

---

## Scope

In scope:
- Authentication and authorization bypass
- Remote code execution
- SQL injection or data exposure
- SSRF / open redirect
- Secrets leaking in logs or responses
- Webhook signature bypass

Out of scope:
- Vulnerabilities that require physical access to the server
- Issues in third-party services (Telegram, Meta, Google, OpenAI)
- Denial of service against a self-hosted instance you do not own

---

## Security Best Practices for Self-Hosting

- Always run behind HTTPS (Caddy handles this automatically in production)
- Use strong, unique passwords for `DB_PASSWORD` and `REDIS_PASSWORD`
- Never expose the Horizon dashboard (`/horizon`) to the public internet — protect it with a firewall or Basic Auth
- Rotate your `APP_KEY` if it is ever exposed
- Set `APP_DEBUG=false` in production to prevent stack traces leaking to users
- Keep Docker images updated — run `docker compose pull && docker compose up -d --build` regularly
