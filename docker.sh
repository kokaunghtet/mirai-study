#!/usr/bin/env bash
set -euo pipefail

echo "=== MiraiStudy Railway Deploy ==="

# -------------------------------------------------------------------
# 1. Environment defaults
# -------------------------------------------------------------------
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_URL="${APP_URL:-http://localhost}"
export PORT="${PORT:-8000}"

# -------------------------------------------------------------------
# 2. Generate .env from Railway environment variables
# -------------------------------------------------------------------
if [ ! -f .env ]; then
    echo ">> Generating .env..."
    cat > .env <<EOF
APP_NAME=${APP_NAME:-MiraiStudy}
APP_ENV=${APP_ENV}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}
APP_LOCALE=${APP_LOCALE:-en}
APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-en}
APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-en_US}
APP_MAINTENANCE_DRIVER=${APP_MAINTENANCE_DRIVER:-file}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-changeme}
MOD_PASSWORD=${MOD_PASSWORD:-changeme}
BCRYPT_ROUNDS=${BCRYPT_ROUNDS:-12}
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-single}
LOG_DEPRECATIONS_CHANNEL=${LOG_DEPRECATIONS_CHANNEL:-null}
LOG_LEVEL=${LOG_LEVEL:-debug}
DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_HOST=${DB_HOST:-}
DB_PORT=${DB_PORT:-}
DB_DATABASE=${DB_DATABASE:-}
DB_USERNAME=${DB_USERNAME:-}
DB_PASSWORD=${DB_PASSWORD:-}
SESSION_DRIVER=${SESSION_DRIVER:-database}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_ENCRYPT=${SESSION_ENCRYPT:-false}
SESSION_PATH=${SESSION_PATH:-/}
SESSION_DOMAIN=${SESSION_DOMAIN:-}
BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-log}
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
CACHE_STORE=${CACHE_STORE:-database}
REDIS_CLIENT=${REDIS_CLIENT:-phpredis}
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PASSWORD=${REDIS_PASSWORD:-null}
REDIS_PORT=${REDIS_PORT:-6379}
MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_SCHEME=${MAIL_SCHEME:-}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_TIMEOUT=${MAIL_TIMEOUT:-10}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@example.com}
MAIL_FROM_NAME=${MAIL_FROM_NAME:-${APP_NAME:-MiraiStudy}}
GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-}
GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET:-}
GOOGLE_REDIRECT_URI=${GOOGLE_REDIRECT_URI:-${APP_URL}/auth/google/callback}
VITE_APP_NAME=${APP_NAME:-MiraiStudy}
EOF
fi

# -------------------------------------------------------------------
# 3. Generate APP_KEY if not set
# -------------------------------------------------------------------
if [ -z "${APP_KEY:-}" ]; then
    echo ">> Generating APP_KEY..."
    php artisan key:generate --force
fi

# -------------------------------------------------------------------
# 4. Storage symlink + directories
# -------------------------------------------------------------------
php artisan storage:link --force 2>/dev/null || true
mkdir -p storage/framework/{sessions,cache,views}
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# -------------------------------------------------------------------
# 5. Database setup
# -------------------------------------------------------------------
DB_CONN="${DB_CONNECTION:-sqlite}"

if [ "$DB_CONN" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    if [ ! -f "$DB_PATH" ]; then
        echo ">> Creating SQLite database at $DB_PATH..."
        mkdir -p "$(dirname "$DB_PATH")"
        touch "$DB_PATH"
    fi
fi

echo ">> Running migrations..."
php artisan migrate --force

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo ">> Running seeders..."
    php artisan db:seed --force
fi

# -------------------------------------------------------------------
# 6. Production caches
# -------------------------------------------------------------------
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# -------------------------------------------------------------------
# 7. Start queue worker in background
# -------------------------------------------------------------------
echo ">> Starting queue worker..."
php artisan queue:work --tries=3 --timeout=60 &

# -------------------------------------------------------------------
# 8. Start web server
# -------------------------------------------------------------------
echo ">> Starting server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
