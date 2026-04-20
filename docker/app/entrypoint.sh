#!/bin/sh
set -e

echo "[entrypoint] Running database migrations..."
php artisan migrate --force

echo "[entrypoint] Caching config, routes, views, events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "[entrypoint] Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "[entrypoint] Syncing public assets to shared volume..."
cp -r /var/www/html/public/. /var/www/html/public-volume/

echo "[entrypoint] Starting php-fpm..."
exec php-fpm --nodaemonize
