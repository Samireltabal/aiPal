# Backup & Restore Guide

This guide covers how to back up and restore your aiPal instance, including the database, uploaded files, and environment configuration.

> **Pre-requisites:** You should have shell access to the machine running aiPal (or Docker exec access). All commands assume you are in the project root (`/path/to/aiPal`).

---

## Table of Contents

- [What to Back Up](#what-to-back-up)
- [Automated Backup Script](#automated-backup-script)
- [Manual Backup](#manual-backup)
  - [Database (PostgreSQL)](#database-postgresql)
  - [Uploaded Files & Media](#uploaded-files--media)
  - [Environment Configuration](#environment-configuration)
- [Restore Procedure](#restore-procedure)
  - [Restoring the Database](#restoring-the-database)
  - [Restoring Uploaded Files](#restoring-uploaded-files)
  - [Restoring .env](#restoring-env)
- [Scheduling Regular Backups (cron)](#scheduling-regular-backups-cron)
- [Backup Verification](#backup-verification)

---

## What to Back Up

| Component | Location | Why |
|---|---|---|
| PostgreSQL database | Managed by the `postgres` container | All user data, tasks, notes, reminders, memories |
| Uploaded files | `./storage/app/uploads/` (inside the `app` container: `/var/www/html/storage/app/uploads/`) | User-uploaded documents for the RAG knowledge base |
| Environment config | `.env` file in project root | AI provider keys, database credentials, app settings |
| Extension tokens | `./storage/app/` — extension-auth tokens | Browser extension connection tokens (auto-regenerated on loss) |

---

## Automated Backup Script

aiPal includes an Artisan command that creates a timestamped backup archive:

```bash
# Inside the app container
docker compose exec app php artisan aipal:backup
```

This will:
1. Dump the PostgreSQL database to `storage/app/backups/`
2. Copy uploaded files into the same backup directory
3. Create a single `.tar.gz` archive named `aipal-backup-YYYY-MM-DD-HHMMSS.tar.gz`

The archive is stored in `storage/app/backups/`. You can copy it off the host:

```bash
docker cp aiPal-app-1:/var/www/html/storage/app/backups/aipal-backup-2025-01-15-120000.tar.gz ./my-backup.tar.gz
```

> **Note:** The container name (`aiPal-app-1`) may differ depending on your Docker Compose project name. Run `docker ps` to verify.

---

## Manual Backup

### Database (PostgreSQL)

Use `pg_dump` directly against the database container:

```bash
docker compose exec postgres pg_dump -U aipal aipal > aipal-db-$(date +%F).sql
```

Or compress on the fly:

```bash
docker compose exec postgres pg_dump -U aipal aipal | gzip > aipal-db-$(date +%F).sql.gz
```

**Defaults:** The database user and name are both `aipal` unless overridden in `.env` (`DB_USERNAME`, `DB_DATABASE`).

### Uploaded Files & Media

Copy the entire uploads directory:

```bash
# Using docker cp
docker cp aiPal-app-1:/var/www/html/storage/app/uploads/ ./uploads-backup/

# Or via a temporary container
docker run --rm -v aiPal_app_data:/data -v $(pwd):/backup alpine tar czf /backup/uploads-backup.tar.gz -C /data uploads/
```

### Environment Configuration

Simply copy the `.env` file:

```bash
cp .env .env.backup.$(date +%F)
```

**Keep your `.env` backup in a secure location** — it contains API keys and database credentials.

---

## Restore Procedure

> **Warning:** Restoring will **overwrite** existing data. Make sure you have a current backup before proceeding.

### Restoring the Database

1. Stop the app container (optional, prevents writes during restore):
   ```bash
   docker compose stop app
   ```

2. Drop and recreate the database (if restoring into a fresh install, skip this):
   ```bash
   docker compose exec postgres psql -U aipal -c "DROP DATABASE IF EXISTS aipal;"
   docker compose exec postgres psql -U aipal -c "CREATE DATABASE aipal;"
   ```

3. Restore from dump:
   ```bash
   # For uncompressed dumps
   docker compose exec -T postgres psql -U aipal aipal < aipal-db-2025-01-15.sql

   # For gzipped dumps
   gunzip -c aipal-db-2025-01-15.sql.gz | docker compose exec -T postgres psql -U aipal aipal
   ```

4. Restart the app container:
   ```bash
   docker compose start app
   ```

### Restoring Uploaded Files

```bash
# Copy files back into the container
docker cp ./uploads-backup/. aiPal-app-1:/var/www/html/storage/app/uploads/

# Fix permissions
docker compose exec app chown -R www-data:www-data storage/app/uploads
```

### Restoring .env

```bash
cp .env.backup.2025-01-15 .env
# Or edit .env manually with your saved values
```

Then restart the stack to pick up changes:

```bash
docker compose up -d --force-recreate
```

---

## Scheduling Regular Backups (cron)

You can schedule aiPal's built-in backup command via the host's crontab:

```bash
# Run daily at 3 AM
0 3 * * * cd /path/to/aiPal && docker compose exec -T app php artisan aipal:backup
```

Or use a more comprehensive backup script that also copies archives off-server:

```bash
#!/bin/bash
# /usr/local/bin/aipal-backup.sh
cd /path/to/aiPal
docker compose exec -T app php artisan aipal:backup
# Copy latest backup to remote storage (example: S3-compatible bucket)
LATEST=$(docker compose exec app ls -t storage/app/backups/*.tar.gz | head -1)
docker cp aiPal-app-1:"$LATEST" /tmp/aipal-latest.tar.gz
aws s3 cp /tmp/aipal-latest.tar.gz s3://my-aipal-backups/
```

Make it executable and add to crontab:

```bash
chmod +x /usr/local/bin/aipal-backup.sh
crontab -e
# Add: 0 3 * * * /usr/local/bin/aipal-backup.sh
```

---

## Backup Verification

Periodically verify your backups can be restored:

1. Spin up a **temporary Docker stack** on a separate machine (or the same host with a different project name).
2. Restore the backup into the temporary stack.
3. Confirm that users, conversations, tasks, and uploaded documents are present.
4. Tear down the temporary stack.

This ensures your backup procedure works before you need it in an emergency.

---

## Restoring After a Fresh Install

If you're restoring onto a completely new server:

1. Follow the [Quick Start](../README.md#quick-start-local) or [VPS Deploy](../README.md#deploy-to-a-vps-with-https) instructions to get the base stack running.
2. **Skip** the `php artisan migrate` step — the restore will include the schema.
3. Restore the database and uploaded files as described above.
4. Run `php artisan aipal:post-restore` (if available) or restart the stack.

---

## See Also

- [Monitoring & Health Checks](./monitoring.md)
- [Troubleshooting Guide](./troubleshooting.md)
- [FAQ](./faq.md)
