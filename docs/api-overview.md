# aiPal REST API Overview

aiPal provides a RESTful API for interacting programmatically with your personal assistant, tasks, notes, reminders, and more. This guide summarizes the main endpoints, authentication methods, and general usage for integrators or power users.

## Authentication
- All API requests require a personal access token, generated in the app under **Settings → API Tokens**.
- Use the token as a Bearer token in the Authorization header:
  ```http
  Authorization: Bearer <your-token>
  ```

## Example Endpoints
- `GET /api/user` — Get current user info
- `POST /api/chat` — Send a message to your AI assistant
- `GET /api/notes` — List all notes
- `POST /api/reminders` — Create a new reminder

> For a full list of endpoints and request/response examples, see the API documentation in the project repository or the generated OpenAPI/Swagger file if available.

## Notes
- API tokens are tied to your user and can be revoked at any time.
- Do not share your API token. Anyone with the token can access your personal AI data.
- For advanced workflows and automation, consider using the API with tools like Zapier or custom scripts.
