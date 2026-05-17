#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Runtime bootstrap. Caching happens HERE (not at build time) because it
# depends on env vars (APP_KEY, DB_*) that Render injects only at runtime.
# Migrations are intentionally NOT run here — they run as Render's
# preDeployCommand (see render.yaml) so they execute exactly once per deploy
# and never race across the web/queue/scheduler processes.
# ---------------------------------------------------------------------------

cd /app

echo "[entrypoint] Linking storage..."
php artisan storage:link --quiet || true

echo "[entrypoint] Caching configuration, routes, views, events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "[entrypoint] Boot complete. Handing off to supervisor."
exec "$@"
