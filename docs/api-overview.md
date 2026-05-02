# aiPal REST API Overview

aiPal provides a RESTful API for interacting programmatically with your personal assistant, tasks, notes, reminders, and more. This guide covers authentication, available endpoints, request/response formats, error handling, and pagination.

---

## Table of Contents

- [Base URL](#base-url)
- [Authentication](#authentication)
- [Endpoints](#endpoints)
  - [Get Current User](#get-current-user)
  - [Update Location](#update-location)
  - [Clear Location](#clear-location)
  - [Health Check](#health-check)
  - [Webhooks](#webhooks)
- [Error Handling](#error-handling)
- [Pagination](#pagination)
- [Rate Limiting](#rate-limiting)
- [Client Examples](#client-examples)
- [API Roadmap](#api-roadmap)

---

## Base URL

All API endpoints are relative to your aiPal instance URL.

| Environment | Base URL |
|---|---|
| Local development | `http://localhost` or `http://localhost:8000` |
| Production (Docker) | `https://your-domain.com` |

---

## Authentication

There are two authentication methods, depending on the endpoint:

### 1. Personal Access Token (Sanctum)

For programmatic API access (typically used for the `GET /api/user` endpoint):

1. Generate a token in the app under **Settings → API Tokens**.
2. Include it in the `Authorization` header:

```http
Authorization: Bearer <your-token>
```

**Example request:**

```bash
curl https://your-domain.com/api/user \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Security notes:**
- Tokens are tied to your user account and can be revoked at any time.
- Do not share your API token — anyone with the token can access your personal AI data.
- Store tokens securely (e.g., environment variables, secret manager).

### 2. Session / Cookie Auth

Endpoints under `/api/v1/` and web UI routes use the browser session cookie established during login. These are not typically called from external scripts; they are consumed by the Livewire frontend and the browser extension.

---

## Endpoints

### Get Current User

Returns the authenticated user's profile and configuration.

```
GET /api/user
```

**Headers:** `Authorization: Bearer <token>`

**Response `200 OK`:**

```json
{
    "id": 1,
    "name": "Alex",
    "email": "alex@example.com",
    "persona_name": "Aria",
    "persona_description": "Helpful and friendly assistant",
    "timezone": "America/New_York",
    "email_briefing_enabled": true,
    "email_briefing_time": "07:00",
    "created_at": "2025-01-01T12:00:00.000000Z"
}
```

**Response `401 Unauthorized`:**

```json
{
    "message": "Unauthenticated."
}
```

---

### Update Location

Sets the user's current location (used for context in AI prompts, weather, timezone hints).

```
POST /api/v1/location
```

**Authentication:** Session (browser cookie)

**Request body (JSON):**

```json
{
    "city": "New York",
    "country": "US",
    "timezone": "America/New_York",
    "latitude": 40.7128,
    "longitude": -74.0060
}
```

All fields are optional but at least one should be provided.

**Response `200 OK`:**

```json
{
    "message": "Location updated.",
    "location": {
        "city": "New York",
        "country": "US",
        "timezone": "America/New_York",
        "latitude": 40.7128,
        "longitude": -74.0060
    }
}
```

---

### Clear Location

Removes the user's stored location.

```
DELETE /api/v1/location
```

**Authentication:** Session (browser cookie)

**Response `200 OK`:**

```json
{
    "message": "Location cleared."
}
```

---

### Health Check

Returns the application health status (no authentication required).

```
GET /healthz
```

**Response `200 OK`:**

```json
{
    "status": "ok"
}
```

This endpoint is intended for load balancers and monitoring services. See the [Monitoring Guide](./monitoring.md) for more health check details.

---

### Webhooks

These endpoints are called by external services, not directly by API consumers. They are documented here for reference.

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/webhooks/telegram` | Incoming Telegram messages (set via `php artisan telegram:set-webhook`) |
| `GET` | `/webhooks/whatsapp` | WhatsApp webhook verification (Meta Cloud API) |
| `POST` | `/webhooks/whatsapp` | Incoming WhatsApp messages |
| `POST` | `/webhooks/email/inbound` | Incoming forwarded email processing |
| `POST` | `/webhooks/workflow/{token}` | Custom workflow triggers (token-authenticated) |

Refer to the corresponding setup guides for configuration details:
- [Telegram Setup](./telegram-setup.md)
- [WhatsApp Setup](./whatsapp-setup.md)
- [Google OAuth (for Gmail forwarding)](./google-oauth-setup.md)

---

## Error Handling

All API endpoints return standard HTTP status codes and a JSON error body.

### Common Status Codes

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Created |
| `400` | Bad request — malformed input or missing required fields |
| `401` | Unauthorized — missing or invalid authentication |
| `403` | Forbidden — authenticated but not authorized |
| `404` | Not found |
| `422` | Unprocessable Entity — validation failure |
| `429` | Too Many Requests — rate limit exceeded |
| `500` | Internal server error |

### Error Response Format

```json
{
    "message": "The city field is required.",
    "errors": {
        "city": ["The city field is required."]
    }
}
```

For `401` responses:

```json
{
    "message": "Unauthenticated."
}
```

---

## Pagination

Currently, the API does not expose list endpoints with pagination. When list endpoints (notes, tasks, reminders) are added in a future release, they will follow this convention:

```http
GET /api/notes?page=1&per_page=15
```

**Response headers:**

```
X-Total-Count: 42
Link: <https://your-domain.com/api/notes?page=2>; rel="next"
```

**Response body structure:**

```json
{
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    }
}
```

---

## Rate Limiting

API endpoints guarded by Sanctum authentication (`/api/*`) are subject to rate limiting:

| Period | Max Requests |
|---|---|
| 1 minute | 60 requests |

When the limit is exceeded, the API returns:

```
Status: 429 Too Many Requests
Retry-After: 42
```

```json
{
    "message": "Too Many Attempts."
}
```

Webhook endpoints and `/healthz` are not rate-limited.

---

## Client Examples

### cURL

```bash
# Get current user
curl -H "Authorization: Bearer YOUR_TOKEN" https://your-domain.com/api/user

# Update location (session cookie required)
curl -X POST https://your-domain.com/api/v1/location \
  -H "Content-Type: application/json" \
  -H "Cookie: session=YOUR_SESSION_COOKIE" \
  -d '{"city": "Berlin", "country": "DE"}'

# Health check (no auth)
curl https://your-domain.com/healthz
```

### JavaScript (fetch)

```javascript
// Get current user
const response = await fetch('https://your-domain.com/api/user', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json',
  },
});
const user = await response.json();
```

### PHP (Guzzle)

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken('YOUR_TOKEN')
    ->get('https://your-domain.com/api/user');

$user = $response->json();
```

---

## API Roadmap

The following endpoints are planned for future releases. Track progress in the [GitHub issues](https://github.com/Samireltabal/aiPal/issues).

| Endpoint | Description | Status |
|---|---|---|
| `GET /api/notes` | List user notes with search | Planned |
| `POST /api/notes` | Create a new note | Planned |
| `DELETE /api/notes/{id}` | Delete a note | Planned |
| `POST /api/chat` | Send a message to AI assistant | Planned |
| `GET /api/reminders` | List reminders | Planned |
| `POST /api/reminders` | Create a new reminder | Planned |
| `PATCH /api/reminders/{id}` | Update a reminder | Planned |
| `GET /api/memories` | List long-term memories | Planned |
| `POST /api/memories` | Add a long-term memory | Planned |

For real-time updates, watch the repository or join the community discussions.

---

## See Also

- [User Roles & Permissions](./user-permissions.md) — Understanding API token scopes and user isolation
- [Browser Extension Setup](./browser-extension.md) — Using tokens for extension integration
- [Monitoring & Health Checks](./monitoring.md) — Health endpoint and uptime monitoring
- [Troubleshooting Guide](./troubleshooting.md) — Common API connection issues
