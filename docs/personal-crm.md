# Personal CRM

aiPal includes a built-in Personal CRM (Customer Relationship Management system for your personal and professional contacts). It tracks people you interact with, logs conversations, reminds you of birthdays, and highlights contacts you haven't spoken to in a while — all manageable through chat or the web UI.

---

## Overview

The Personal CRM helps you:

- **Keep an address book** — names, emails, phone numbers, tags, notes, and custom fields
- **Auto-populate contacts** — from forwarded emails and Gmail drafts
- **Log interactions** — track when you last spoke and what was discussed
- **Get birthday reminders** — daily checks for upcoming birthdays
- **Find stale contacts** — people you haven't connected with in a while
- **Use AI tools** — manage everything by chatting with your assistant

---

## Getting Started

The CRM is enabled by default for all users. There's no initial setup required — it starts populating as you use your connected email accounts.

### Access via the Web UI

Navigate to **`/people`** in your aiPal instance (or click "People" in the navigation menu). Here you can:

- **Browse** — scroll through your contacts list with search and tag filters
- **View details** — click a person to see their full profile, interaction timeline, and edit their information
- **Add a person** — use the inline create form at the top of the list
- **Export** — download your contacts as CSV or JSON
- **Bulk tag** — select multiple contacts and apply tags in one action
- **Merge duplicates** — the system can detect and merge duplicate contacts

### Access via Chat

Just ask your assistant:

> *"Who are my contacts?"*
> *"Add Jane Doe, jane@example.com, colleague"*
> *"When did I last talk to Mark?"*
> *"Show me people I haven't contacted in a month"*
> *"Log a call with Sarah about the project review"*

The assistant uses 7 dedicated AI tools to manage the CRM (see [AI Tools](#ai-tools) below).

---

## Features

### Contact Profiles

Each person record can store:

| Field | Type | Description |
|-------|------|-------------|
| Name | string | Full name (required) |
| Emails | array | Multiple email addresses with primary toggle |
| Phones | array | Multiple phone numbers with primary toggle |
| Tags | array | Freeform tags for grouping (`colleague`, `friend`, `client`, etc.) |
| Notes | text | Free-text notes about this person |
| Birthday | date | Used for birthday reminders |
| Custom fields | JSONB | Any additional structured data |

### Interaction Timeline

Every profile has a timeline showing all logged interactions:

- **Type** — call, email, meeting, chat, or custom
- **Date & time** — when the interaction occurred
- **Summary** — a brief description
- **Linked notes/tasks** — cross-references to related content

### Auto-Population

The CRM automatically creates and updates contact records from:

- **Forwarded emails** — when you forward an email to aiPal, the sender is added to your contacts
- **Gmail drafts** — recipients of your drafts are suggested as contacts
- **Chat mentions** — if you mention a person's name in chat, the assistant may ask if you'd like to add them

> Auto-population is **email-only** (not from Telegram/WhatsApp, as those are private user↔bot conversations).

### Birthday Reminders

A daily scheduled command (`people:birthday-check`) checks every morning at 07:00 for birthdays in the next 7 days. If a match is found, the assistant reminds you via email and in the dashboard.

- **7-day lookahead** — you get advance notice, not just same-day
- **Idempotent** — you won't receive duplicate reminders for the same birthday in the same year
- **Dashboard widget** — upcoming birthdays appear on the dashboard

### Stale Contacts Dashboard Widget

A widget on the main dashboard shows people you haven't contacted in 30 days or more. This helps you stay connected with important relationships.

---

## AI Tools

The CRM exposes 7 AI tools that the assistant can use during conversation. You can enable or disable each tool in **Settings → AI Tools**.

| Tool | Description | Example Prompt |
|------|-------------|----------------|
| `FindPerson` | Search for a person by name, email, or tag | "Find John from the client tag" |
| `ListPeople` | List contacts with optional filters | "Show my colleagues" |
| `CreatePerson` | Add a new contact | "Add Maria as a contact, maria@example.com" |
| `UpdatePerson` | Edit an existing contact's details | "Update Jane's phone to +1-555-1234" |
| `LogInteraction` | Record an interaction with a contact | "Log that I called Mom yesterday" |
| `RecentInteractions` | Show recent activity with a person | "What did I talk about with Alex last week?" |
| `FindStaleContacts` | Find contacts you haven't spoken to in N days | "Who haven't I reached out to in a month?" |

---

## API

The CRM is also accessible programmatically via the REST API. See the [API Reference](./api-reference.md#people-personal-crm) for full endpoint documentation.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/people` | List people |
| `POST` | `/api/v1/people` | Create a person |
| `GET` | `/api/v1/people/{id}` | Get person details |
| `PUT` | `/api/v1/people/{id}` | Update a person |
| `DELETE` | `/api/v1/people/{id}` | Delete a person |
| `GET` | `/api/v1/interactions` | List interactions |
| `POST` | `/api/v1/interactions` | Log an interaction |

---

## Merging Duplicates

If you end up with duplicate contacts (e.g., "John Smith" and "Jon Smith"), use the merge feature:

1. Go to **People → select the duplicates** using the checkboxes
2. Click **"Merge"** — the system will:
   - Union all tags
   - Backfill missing fields with values from the other record
   - Reassign all emails, phones, and interactions to the surviving record
   - Keep the most recent `last_contact_at` date
   - Soft-delete the duplicate
3. Confirm the merge in the dialog

Merges are transactional — if anything fails, nothing changes.

---

## Export & Import

### Export

From the `/people` page, click **"Export"** and choose:

- **CSV** — opens in any spreadsheet application
- **JSON** — preserves all fields including custom data

### Import

There is no built-in bulk import yet. To migrate from another CRM, use the API's `POST /api/v1/people` endpoint with a script.

---

## Related

- [API Reference](./api-reference.md) — full REST API documentation for people and interactions
- [Backup & Restore](./backup-restore.md) — include the `people` and `interactions` tables in your backups
- [Daily Briefing](./daily-briefing.md) — the briefing can include stale contact reminders
