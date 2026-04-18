# syntax=docker/dockerfile:1
# Multi-stage, multi-arch (amd64 + arm64) — PHP 8.4 FPM

# ─── Stage 1: Composer dependencies ──────────────────────────────────────────
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
    && composer dump-autoload --optimize --no-dev

# ─── Stage 2: Node / frontend build ──────────────────────────────────────────
FROM node:22-alpine AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .
COPY --from=composer-deps /app/vendor ./vendor
RUN npm run build

# ─── Stage 3: Production image ────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS production

# System deps + PHP extensions
RUN apk add --no-cache \
        supervisor \
        libpq-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        zip \
        unzip \
        git \
        curl \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        opcache \
        pcntl \
        bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy app
COPY --from=composer-deps /app /var/www/html
COPY --from=node-build /app/public/build /var/www/html/public/build

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Supervisor config (manages php-fpm + horizon + scheduler)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ─── Development override ──────────────────────────────────────────────────────
FROM production AS development

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
