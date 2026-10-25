#!/usr/bin/env bash
# One-command deploy for an Ubuntu/Debian server.
#
#   ./deploy.sh https://your-domain.com     # first time (domain optional)
#   ./deploy.sh                             # every later update
#
# Uses the fixed "laboratory" Compose project so updates find the same
# containers and named volumes regardless of the checkout directory.
# The database is created only if it does not exist; an existing database
# container is never recreated by this script. The app image is rebuilt and
# the app container is updated separately.
set -euo pipefail
cd "$(dirname "$0")"

[ "$(id -u)" -eq 0 ] || exec sudo -E bash "$0" "$@"

ENV_FILE=.env.deploy
COMPOSE="docker compose -p laboratory --env-file $ENV_FILE -f docker-compose.prod.yml"

set_kv() { # set_kv KEY VALUE  -> upsert in $ENV_FILE
    if grep -q "^$1=" "$ENV_FILE"; then sed -i "s|^$1=.*|$1=$2|" "$ENV_FILE"; else echo "$1=$2" >> "$ENV_FILE"; fi
}

# 1. Docker ------------------------------------------------------------------
if ! command -v docker >/dev/null 2>&1; then
    echo "==> Installing Docker..."
    curl -fsSL https://get.docker.com | sh
fi
systemctl enable --now docker >/dev/null 2>&1 || true

# 2. Swap on small servers (the image build needs ~1.5 GB) --------------------
mem_mb=$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)
if [ "$mem_mb" -lt 2000 ] && [ -z "$(swapon --show --noheadings)" ]; then
    echo "==> Only ${mem_mb} MB RAM: adding a 2 GB swap file for the build..."
    if fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile; then
        grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
    else
        echo "    (could not create swap, continuing)"
    fi
fi

# 3. Secrets / config (created once, never overwritten) -----------------------
# Never generate a new DB password when an existing production database is present.
# The original .env.deploy is needed so the app keeps using that database's credentials.
if [ ! -f .env.deploy ] && docker container inspect laboratory-db-1 >/dev/null 2>&1; then
    echo "!! Found existing container laboratory-db-1, but .env.deploy is missing."
    echo "!! Restore the original .env.deploy from backup before deploying; refusing to generate new credentials."
    exit 1
fi
if [ ! -f "$ENV_FILE" ]; then
    echo "==> Creating $ENV_FILE with generated secrets..."
    umask 077
    cat > "$ENV_FILE" <<EOF
APP_URL=http://localhost:8080
APP_KEY=base64:$(openssl rand -base64 32)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=lims
DB_USERNAME=lims
DB_PASSWORD=$(openssl rand -hex 16)
DB_SSLMODE=disable
SESSION_SECURE_COOKIE=false
EOF
fi
if [ -n "${1:-}" ]; then
    set_kv APP_URL "${1%/}"
    case "$1" in https://*) set_kv SESSION_SECURE_COOKIE true ;; *) set_kv SESSION_SECURE_COOKIE false ;; esac
fi

# 4. Update code, ensure the database exists, then update only the app -------
if [ -d .git ]; then
    git -c safe.directory="$PWD" pull --ff-only || echo "(git pull skipped)"
fi

echo "==> Ensuring the existing database is running (create only if absent)..."
# --no-recreate preserves an existing PostgreSQL container and its configuration.
# If no db container exists for this Compose project, Compose creates it normally.
$COMPOSE up -d --no-recreate db

echo "==> Building and updating the application (database will not be recreated)..."
# --no-deps prevents this app update from touching the database service.
$COMPOSE up -d --build --no-deps app

# 5. Wait until healthy (migrations run during start) -------------------------
port=$(grep '^APP_PORT=' "$ENV_FILE" | cut -d= -f2 || true)
port=${port:-8080}
echo "==> Waiting for the app on 127.0.0.1:$port ..."
for _ in $(seq 1 90); do
    curl -fsS "http://127.0.0.1:$port/up" >/dev/null 2>&1 && ok=1 && break
    sleep 3
done
if [ -z "${ok:-}" ]; then
    echo "!! App did not become healthy. Logs:"; $COMPOSE logs --tail 40 app; exit 1
fi

# 6. First run only: create roles + Super Admin with a random password --------
users=$($COMPOSE exec -T app php artisan tinker --execute='echo App\Models\User::count();' | tr -dc '0-9')
if [ "${users:-1}" = "0" ]; then
    pw=$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-16)
    $COMPOSE exec -T app php artisan db:seed --class=RolesAndPermissionsSeeder --force --no-interaction >/dev/null
    $COMPOSE exec -T -e NEWPW="$pw" app php artisan tinker --execute='App\Models\User::where("email","admin@labsystem.pk")->update(["password"=>bcrypt(getenv("NEWPW"))]);' >/dev/null
    echo
    echo "==> First-run Super Admin (shown once - change it after login):"
    echo "    email:    admin@labsystem.pk"
    echo "    password: $pw"
fi

echo
echo "==> Done. App is running at $(grep '^APP_URL=' "$ENV_FILE" | cut -d= -f2) (container port 127.0.0.1:$port)."
echo "    Point your reverse proxy (nginx) at http://127.0.0.1:$port - see README."
