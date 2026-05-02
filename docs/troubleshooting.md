# Troubleshooting Guide

Having trouble setting up or using aiPal? Here are some common issues and their solutions:

## Docker Fails to Start

- **Issue**: Docker containers won't start.
  - **Solution**: Verify that Docker is installed and running. Check Docker Desktop on macOS/Windows or ensure your user is part of the `docker` group on Linux.

## Database Connection Fails

- **Issue**: Unable to connect to the PostgreSQL database.
  - **Solution**: Check that PostgreSQL service is running. Verify your `.env` configuration for correct database credentials.

## AI Provider Key Errors

- **Issue**: Invalid or missing AI provider key.
  - **Solution**: Ensure the AI provider key is set in the `.env` file. Double-check the key value and its accessibility.

## Webhook Registration Fails

- **Issue**: Unable to register webhooks after deployment.
  - **Solution**: Make sure your server has valid HTTPS setup. Verify webhook URLs and re-run webhook setup commands.

## Memory or Disk Space Issues

- **Issue**: Insufficient memory or disk space.
  - **Solution**: Check available system resources. Consider upgrading server specs or cleaning up disk space.

## pgvector Extension Missing or Not Enabled

- **Issue**: Errors like "extension pgvector does not exist" or vector search failures.
  - **Solution**: Ensure your PostgreSQL image supports pgvector (use `pgvector/pgvector:pg16` or similar in docker-compose). Run `CREATE EXTENSION vector;` manually via `docker compose exec postgres psql -U postgres -d aipal -c "CREATE EXTENSION IF NOT EXISTS vector;"`. Re-run migrations if needed.

## Ollama / Local Models Not Working

- **Issue**: Ollama profile fails to start or models not pulled.
  - **Solution**: Use `docker compose --profile ollama up -d`. Manually pull models: `docker compose exec ollama ollama pull llama3.2` and `ollama pull nomic-embed-text`. Check logs with `docker compose logs ollama`. On low-RAM devices (e.g. Pi 4GB), use smaller models like `llama3.2:1b`.

## Voice Features (STT/TTS) Failing

- **Issue**: Push-to-talk or text-to-speech not functioning.
  - **Solution**: Ensure `OPENAI_API_KEY` (or Gemini) is set for Whisper STT and TTS. For ElevenLabs, set `ELEVENLABS_API_KEY`. Check browser permissions for microphone. Test via Settings > Voice. On VPS, ensure no proxy blocks audio endpoints.

## Redis Connection Problems

- **Issue**: Queue or cache errors related to Redis.
  - **Solution**: Verify Redis container is running (`docker compose ps`). Check `REDIS_HOST` and `REDIS_PASSWORD` in `.env`. Restart with `docker compose restart redis`.

## Permission Denied Errors on Linux

- **Issue**: File permission or socket errors when running containers.
  - **Solution**: Add your user to docker group: `sudo usermod -aG docker $USER` then log out/in. For storage issues, ensure `storage/` and `bootstrap/cache` have write permissions: `sudo chown -R $USER:www-data storage bootstrap/cache`.

## New Issues?

If you encounter a problem not listed here, please open an Issue on our [GitHub Issues page](https://github.com/Samireltabal/aiPal/issues).

---

Keeping this guide updated helps everyone, so feel free to suggest improvements or additions.
