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

## New Issues?

If you encounter a problem not listed here, please open an Issue on our [GitHub Issues page](https://github.com/Samireltabal/aiPal/issues).

---

Keeping this guide updated helps everyone, so feel free to suggest improvements or additions.
