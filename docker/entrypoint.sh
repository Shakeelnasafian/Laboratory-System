#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Runtime bootstrap. Everything heavy (composer, npm, asset build) already
# happened at image build time; this only does what depends on runtime config:
#   1. create .env from the baked non-secret defaults
#   2. generate APP_KEY if none was provided
#   3. wait for the database, then run migrations
#   4. cache config/routes/views, link storage
# Real environment variables always override values in .env.
#
# Optional switches:
#   RUN_MIGRATIONS=false   skip migrations (e.g. when a deploy hook runs them)
#   RUN_SEEDERS=true       also run `db:seed` (demo data; not idempotent-safe
#                          for production data, so off by default)
#   DB_WAIT_RETRIES=30     attempts (2s apart) to reach the DB before giving up
# ---------------------------------------------------------------------------

cd /app

# 1. .env -------------------------------------------------------------------
if [ ! -f .env ]; then
    echo "[entrypoint] Creating .env from image defaults..."
    cp docker/env.defaults .env
fi

# 2. APP_KEY ----------------------------------------------------------------
if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env; then
    echo "[entrypoint] No APP_KEY provided - generating a new one."
    echo "[entrypoint] WARNING: it is stored only inside this container. Sessions and"
    echo "[entrypoint]          encrypted data are lost when the container is recreated;"
    echo "[entrypoint]          set APP_KEY in the environment to keep it stable."
    php artisan key:generate --force --no-interaction
fi

# Drop caches left over from a previous run so new env values take effect.
# Not cache:clear - the DB-backed cache table may not exist before migrations.
for cmd in config:clear route:clear view:clear event:clear; do
    php artisan "$cmd" --quiet || true
done

# 3. Database + migrations --------------------------------------------------
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    retries="${DB_WAIT_RETRIES:-30}"
    attempt=1
    echo "[entrypoint] Running migrations (waiting for database if needed)..."
    until php artisan migrate --force --no-interaction; do
        if [ "$attempt" -ge "$retries" ]; then
            echo "[entrypoint] ERROR: migrations failed after $attempt attempts." >&2
            exit 1
        fi
        echo "[entrypoint] Migration attempt $attempt/$retries failed; retrying in 2s..."
        attempt=$((attempt + 1))
        sleep 2
    done

    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        echo "[entrypoint] Seeding database..."
        php artisan db:seed --force --no-interaction
    fi
fi

# 4. Caches + storage -------------------------------------------------------
echo "[entrypoint] Linking storage..."
php artisan storage:link --quiet || true

echo "[entrypoint] Caching configuration, routes, views, events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# .env / key:generate / caches were written as root; keep Laravel's writable
# dirs usable by the web user.
chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] Boot complete. Handing off to supervisor."
exec "$@"
