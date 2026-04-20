# Contributing to aiPal

Thank you for your interest in contributing! This document explains how to set up a development environment, run tests, and submit a pull request.

---

## Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Code Style](#code-style)
- [Testing](#testing)
- [Submitting a Pull Request](#submitting-a-pull-request)
- [Commit Messages](#commit-messages)
- [Reporting Bugs](#reporting-bugs)
- [Security Vulnerabilities](#security-vulnerabilities)

---

## Getting Started

1. **Fork** the repository on GitHub
2. **Clone** your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/aiPal.git
   cd aiPal
   ```
3. **Add the upstream remote**:
   ```bash
   git remote add upstream https://github.com/Samireltabal/aiPal.git
   ```

---

## Development Setup

**Requirements:** PHP 8.4, Composer 2, Node 22, Docker (for Postgres + Redis)

```bash
# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy environment file
cp .env.example .env

# Start Postgres and Redis via Docker
docker compose up -d postgres redis

# Generate app key and run migrations
php artisan key:generate
php artisan migrate

# Start the dev server (Laravel + Vite + queue worker + Pail logs)
composer run dev
```

Open **http://localhost:8000** and complete the onboarding wizard.

### Environment Variables

At minimum, set one AI provider key in `.env`:

```env
ANTHROPIC_API_KEY=sk-ant-...   # or OPENAI_API_KEY, GEMINI_API_KEY, DEEPSEEK_API_KEY
OPENAI_API_KEY=sk-...          # required for embeddings (pgvector) and STT (Whisper)
```

---

## Code Style

This project uses **Laravel Pint** for code formatting.

```bash
# Check formatting
vendor/bin/pint --test

# Fix formatting
vendor/bin/pint
```

Pint runs automatically in CI — PRs with formatting errors will fail.

**Conventions:**
- `declare(strict_types=1)` on every PHP file
- Constructor property promotion for all new classes
- Explicit return types and parameter type hints everywhere
- Immutable DTOs for data crossing service boundaries
- No `var_dump`, `dd()`, or `die()` in committed code

---

## Testing

PHPUnit is the test framework. All PRs must maintain **≥ 80% coverage** on changed files.

```bash
# Run all tests
php artisan test --compact

# Run a single test file
php artisan test --compact tests/Feature/ExampleTest.php

# Run tests matching a name
php artisan test --compact --filter=testName

# Run with coverage
php artisan test --coverage --min=80
```

**Test guidelines:**
- Feature tests for every controller/endpoint
- Unit tests for every service class
- Use factories — never raw `User::create()` calls
- Fake AI provider responses for deterministic tests — do not make real API calls in tests
- Tests must be PHPUnit classes — no Pest

---

## Submitting a Pull Request

1. **Sync with upstream** before starting:
   ```bash
   git fetch upstream
   git checkout main
   git merge upstream/main
   ```

2. **Create a feature branch**:
   ```bash
   git checkout -b feat/my-feature
   # or
   git checkout -b fix/some-bug
   ```

3. **Write tests first** (TDD — red → green → refactor)

4. **Run Pint** before committing:
   ```bash
   vendor/bin/pint
   ```

5. **Run the full test suite**:
   ```bash
   php artisan test --compact
   ```

6. **Push and open a PR** against `main`:
   ```bash
   git push -u origin feat/my-feature
   ```

7. Fill in the **PR template** — describe what changed and include a test plan.

---

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>: <short description>

<optional body>
```

Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`

Examples:
```
feat: add Slack notification channel for reminders
fix: handle expired WhatsApp access token gracefully
docs: add Raspberry Pi setup guide
test: add feature tests for GmailTool
```

---

## Reporting Bugs

Use the [Bug Report template](.github/ISSUE_TEMPLATE/bug_report.md). Include:

- Steps to reproduce
- Expected vs actual behavior
- Laravel log output (with secrets redacted)
- Your deployment type (Docker / VPS / Raspberry Pi)
- PHP and Docker versions

---

## Security Vulnerabilities

**Do not open a public issue for security vulnerabilities.**

See [SECURITY.md](./SECURITY.md) for the responsible disclosure process.
