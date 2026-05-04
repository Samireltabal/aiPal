# Monitoring & Observability

A guide to monitoring your aiPal instance — checking health, viewing logs, inspecting queues, and setting up proactive alerts.

---

## Health Check

aiPal provides a simple health check endpoint that requires no authentication:

```bash
curl http://localhost/health
```

Returns `200 OK` when the application is running. Use this with uptime monitors (e.g., UptimeRobot, Better Uptime, Healthchecks.io) or your own watchdogs.

```bash
# Example: cron-based health check
curl -s -o /dev/null -w "%{http_code}" http://localhost/health
# => 200
```

---

## Checking Service Status

### Docker Containers

```bash
# List all containers and their status
docker compose ps

# Expected output — all should show "Up" or "Up (healthy)"
NAME                  STATUS
app                   Up 2 hours
postgres              Up 2 hours (healthy)
redis                 Up 2 hours
horizon               Up 2 hours
scheduler             Up 2 hours
nginx                 Up 2 hours
ollama                Up 2 hours    # only if --profile ollama is used
caddy                 Up 2 hours    # only in production (docker-compose.prod.yml)
```

### Container Resource Usage

```bash
# CPU and memory per container
docker stats --no-stream

# Disk usage
docker system df

# Inspect a specific container's logs for errors
docker compose logs --tail=50 app
```

---

## Viewing Logs

### Application Logs

Laravel logs are stored inside the `app` container. View them with:

```bash
# Tail the latest log file
docker compose exec app tail -f storage/logs/laravel.log

# Or use Laravel Pail (included) for a friendly log viewer
docker compose exec app php artisan pail
```

### Per-Service Logs

```bash
# Web server (Nginx)
docker compose logs nginx

# Queue worker (Horizon)
docker compose logs horizon

# Scheduler
docker compose logs scheduler

# Database
docker compose logs postgres

# Redis
docker compose logs redis

# AI (Ollama, if used)
docker compose logs ollama
```

### Centralised Log Viewing

For production deployments, consider forwarding logs to a central service:

- **Loki** + **Grafana** — lightweight, Docker-friendly log aggregation
- **Papertrail** — simple cloud log management (add `logs-papertrail` service)
- **Better Stack / Logtail** — structured logging with alerting

---

## Horizon Dashboard

Laravel Horizon provides a real-time dashboard for monitoring queues. Access it at:

```
http://<your-server>/horizon
```

In production, secure this route by adding the `HORIZON_AUTH_TOKEN` environment variable or restricting access via IP allowlist.

### What to Watch

| Metric | Healthy | Warning | Critical |
|--------|---------|---------|----------|
| Queue throughput | Processing steadily | Stalled for >5 min | Stalled for >15 min |
| Failed jobs | 0 | 1–5 recent failures | 5+ or recurring failures |
| Wait time | < 5 seconds | 5–30 seconds | > 30 seconds |
| Worker processes | All running | Some stopped | All stopped |

### Failed Jobs

Inspect and retry failed jobs directly from Horizon or via the command line:

```bash
# List failed jobs
docker compose exec app php artisan queue:failed

# Retry a specific failed job
docker compose exec app php artisan queue:retry <id>

# Retry all failed jobs
docker compose exec app php artisan queue:retry all

# Delete a failed job
docker compose exec app php artisan queue:forget <id>
```

---

## Database Monitoring

### Connection & Size

```bash
# Check active connections
docker compose exec postgres psql -U aipal -c "SELECT count(*) FROM pg_stat_activity;"

# Database size
docker compose exec postgres psql -U aipal -c "
  SELECT
    pg_database_size('aipal') / 1024 / 1024 AS size_mb,
    pg_size_pretty(pg_database_size('aipal')) AS size_pretty;
"

# Table sizes
docker compose exec postgres psql -U aipal -c "
  SELECT
    relname AS table_name,
    pg_size_pretty(pg_total_relation_size(relid)) AS total_size
  FROM pg_catalog.pg_statio_user_tables
  ORDER BY pg_total_relation_size(relid) DESC;
"
```

### Vector Index Health

aiPal uses HNSW indexes on pgvector columns. Check that they exist and are in use:

```bash
docker compose exec postgres psql -U aipal -c "
  SELECT
    indexname,
    indexdef
  FROM pg_indexes
  WHERE indexdef LIKE '%vector%';
"
```

---

## Redis Monitoring

```bash
# Check Redis is reachable
docker compose exec redis redis-cli ping
# => PONG

# Memory usage
docker compose exec redis redis-cli info memory | grep used_memory_human

# Queue size (number of pending jobs)
docker compose exec redis redis-cli LLEN queues:default
```

---

## Proactive Monitoring Setup

### 1. External Uptime Monitor

Point a free uptime monitor (e.g., [Healthchecks.io](https://healthchecks.io), [Better Uptime](https://betteruptime.com)) at:

```
https://yourdomain.com/health
```

### 2. Cron Job Self-Check

Add a cron job on the host to regularly verify all services are running:

```bash
#!/bin/bash
# /usr/local/bin/check-aipal.sh
cd /path/to/aiPal

# Check all containers are up
if ! docker compose ps --status running | grep -q "app"; then
  echo "aiPal app container is down!" | mail -s "aiPal Alert" you@example.com
fi

# Check Horizon
if ! docker compose exec app php artisan horizon:status | grep -q "running"; then
  echo "Horizon is not running!" | mail -s "aiPal Alert" you@example.com
fi

# Check health endpoint
if ! curl -sf http://localhost/health > /dev/null; then
  echo "Health check failed!" | mail -s "aiPal Alert" you@example.com
fi
```

Add to crontab:
```bash
*/5 * * * * /usr/local/bin/check-aipal.sh
```

### 3. Database Backup Verification

Extend your [backup script](./backup-restore.md) to verify the backup is valid:

```bash
# After creating the backup dump, verify it
gunzip -c "$BACKUP_DIR/db-$TIMESTAMP.sql.gz" | head -50 | grep -q "pg_dump"
if [ $? -eq 0 ]; then
  echo "Backup verified: valid SQL dump"
else
  echo "Backup verification FAILED" | mail -s "aiPal Backup Alert" you@example.com
fi
```

### 4. Disk Space Monitoring

```bash
# Warn if disk usage exceeds 80%
df -h / | awk 'NR==2 {print $5}' | grep -v '8[0-9]%\|9[0-9]%\|100%'
```

---

## Logging Configuration

### Log Level

Set the log level in `.env`:

```env
LOG_LEVEL=debug      # development — verbose
LOG_LEVEL=warning    # production — only warnings and above
LOG_LEVEL=emergency  # production — critical errors only
```

### Log Channel

The default channel is `stack` (single log file). For production, consider:

```env
LOG_CHANNEL=papertrail
PAPERTRAIL_URL=logs.papertrailapp.com
PAPERTRAIL_PORT=12345
```

---

## Performance Considerations

### Queue Workers

Adjust the number of queue workers based on your server's resources:

```env
QUEUE_WORKERS=3      # default; reduce for low-memory environments
```

On a **Raspberry Pi 4 (4 GB RAM)**, set `QUEUE_WORKERS=1` to avoid OOM.

### Database Connection Pooling

PostgreSQL can handle many concurrent connections, but each consumes memory. The default `max_connections` in the Docker image is 100 — suitable for single-user and small team deployments.

### Redis Memory

Redis stores queue jobs and cache data. If you see memory pressure, adjust the `maxmemory` policy in the Redis config or set a TTL on cached items.

---

## Docker Healthcheck Status

All aiPal services include health checks. Verify them:

```bash
docker inspect --format='{{json .State.Health.Status}}' $(docker compose ps -q postgres)
# => "healthy"
```

Services that have health checks: `postgres`, `redis`, `app`.

---

## Related

- [Troubleshooting Guide](./troubleshooting.md) — resolving specific errors
- [Backup & Restore](./backup-restore.md) — data backup procedures
- [Deploy to VPS](./deploy-vps.md) — production deployment considerations
