#!/usr/bin/env bash
set -e

: "${PORT:=80}"

echo "=== MiraiStudy Starting ==="
echo "PORT: ${PORT}"

# ─── Fix MPM conflict at runtime (guaranteed to run every start) ───
echo "=== Fixing Apache MPM ==="
# Remove ALL MPM modules first
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2dismod mpm_prefork 2>/dev/null || true
# Force-clean any leftover symlinks
rm -f /etc/apache2/mods-enabled/mpm_*.conf /etc/apache2/mods-enabled/mpm_*.load
# Enable ONLY prefork (required for mod_php)
a2enmod mpm_prefork
a2enmod rewrite

# ─── Configure Apache port ─────────────────────────────────────────
echo "=== Configuring Apache port ==="
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ─── Laravel setup ─────────────────────────────────────────────────
echo "=== Checking .env ==="
if [ ! -f .env ]; then
    echo "WARNING: No .env file found, copying from .env.example"
    cp .env.example .env 2>/dev/null || true
fi

echo "=== Generating APP_KEY if needed ==="
if grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "APP_KEY already set"
else
    php artisan key:generate --force 2>/dev/null || echo "WARNING: Could not generate APP_KEY"
fi

echo "=== Caching config ==="
php artisan config:cache 2>&1 || echo "WARNING: config:cache failed"
php artisan route:cache 2>&1 || echo "WARNING: route:cache failed"
php artisan view:cache 2>&1 || echo "WARNING: view:cache failed"

echo "=== Storage link ==="
php artisan storage:link --force 2>&1 || echo "WARNING: storage:link failed"

echo "=== Fixing permissions ==="
touch storage/logs/laravel.log 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache

echo "=== Running migrations ==="
php artisan migrate --force 2>&1 || echo "WARNING: migrate failed"

echo "=== Running seeders ==="
php artisan db:seed --force 2>&1 || echo "WARNING: seeders failed"

echo "=== Starting queue worker ==="
php artisan queue:work --tries=3 --timeout=60 &

# ─── Verify Apache config before starting ──────────────────────────
echo "=== Verifying Apache config ==="
apache2ctl configtest 2>&1 || { echo "ERROR: Apache config test failed"; exit 1; }

echo "=== Starting Apache on port ${PORT} ==="
exec apache2-foreground
