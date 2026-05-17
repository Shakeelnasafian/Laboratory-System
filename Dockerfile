# syntax=docker/dockerfile:1
#
# Render deployment image: FrankenPHP (classic mode) + Neon Postgres.
# Single all-in-one container: web + queue worker + scheduler via Supervisor.

# ---------------------------------------------------------------------------
# Stage 1: Build front-end assets (Vite + Tailwind) with Node
# ---------------------------------------------------------------------------
FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

# Source needed for Vite input + Tailwind class scanning
COPY resources/ resources/
COPY vite.config.js ./
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2: Composer dependencies (production, no dev)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
# Defer scripts/autoloader; artisan package:discover runs in the final stage
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# ---------------------------------------------------------------------------
# Stage 3: Runtime — FrankenPHP, PHP 8.3 (>= composer's php ^8.2 constraint)
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.3-bookworm AS runtime

# PHP extensions:
#   pdo_pgsql/pgsql -> Neon Postgres
#   gd, exif        -> barryvdh/laravel-dompdf
#   zip, intl, bcmath -> framework/app deps
#   pcntl           -> queue:work / schedule:work signal handling
#   opcache         -> production performance
RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        gd \
        exif \
        zip \
        intl \
        bcmath \
        pcntl \
        opcache

# Supervisor runs web + queue + scheduler in one container
RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

# Strip Linux file capabilities from the FrankenPHP binary. The dunglas image
# sets cap_net_bind_service so it can bind :80/:443, but we bind Render's high
# $PORT (10000) instead. Exec'ing a capability-carrying binary under Supervisor
# in Render's restricted runtime fails with "EPERM" (exit 127) — remove them.
RUN setcap -r "$(command -v frankenphp)" || true

WORKDIR /app

# Production PHP + opcache tuning
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Application source
COPY . .

# Vendor dir + built assets from earlier stages
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Composer needed only to regenerate the optimized autoloader + run discovery
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && rm /usr/bin/composer

# FrankenPHP/Caddy config and process manager
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Laravel writable dirs (Render runs containers as root by default)
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Documentation only — Render injects $PORT, Caddyfile binds to it.
EXPOSE 10000

ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf", "-n"]
