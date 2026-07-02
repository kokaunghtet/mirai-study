#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(pwd)"

# ──────────────────────────────────────────────
# MiraiStudy — Railway deployment build + start
# ──────────────────────────────────────────────

# ── BUILD PHASE (skip if already built) ───────
if [ ! -f "$APP_DIR/.build-done" ]; then

  echo "==> Installing PHP 8.5 and extensions…"
  apt-get update -yqq
  apt-get install -yqq software-properties-common curl unzip
  add-apt-repository -y ppa:ondrej/php
  apt-get update -yqq
  apt-get install -yqq \
    php8.5 php8.5-cli php8.5-common php8.5-mbstring php8.5-xml \
    php8.5-curl php8.5-zip php8.5-sqlite3 php8.5-gd php8.5-bcmath \
    php8.5-intl php8.5-readline php8.5-tokenizer php8.5-fileinfo \
    php8.5-fpm

  echo "==> Installing Node.js 20…"
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -yqq nodejs

  echo "==> Installing Composer…"
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

  echo "==> Installing Caddy…"
  apt-get install -yqq debian-keyring debian-archive-keyring apt-transport-https
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list
  apt-get update -yqq
  apt-get install -yqq caddy

  echo "==> Installing PHP dependencies…"
  composer install --no-dev --optimize-autoloader --no-interaction

  echo "==> Installing Node dependencies and building assets…"
  npm ci --ignore-scripts
  npm run build

  echo "==> Setting up environment…"
  [ -f .env ] || cp .env.example .env
  if grep -q '^APP_KEY=$' .env 2>/dev/null || ! grep -q '^APP_KEY=' .env 2>/dev/null; then
    php artisan key:generate --force
  fi

  echo "==> Ensuring SQLite database exists…"
  mkdir -p database
  [ -f database/database.sqlite ] || touch database/database.sqlite

  echo "==> Running migrations…"
  php artisan migrate --force

  echo "==> Caching config/routes/views…"
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan icons:cache 2>/dev/null || true

  echo "==> Setting permissions…"
  chmod -R 775 storage bootstrap/cache

  echo "==> PHP ini overrides (upload limits)…"
  mkdir -p /etc/php/8.5/fpm/conf.d
  cat > /etc/php/8.5/fpm/conf.d/99-miraistudy.ini <<INI
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 60
INI

  echo "==> Configuring FPM pool (Unix socket)…"
  cat > /etc/php/8.5/fpm/pool.d/www.conf <<FPM
[www]
user = www-data
group = www-data
listen = /run/php/php8.5-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
clear_env = no
FPM

  echo "==> Configuring Caddy…"
  cat > /etc/caddy/Caddyfile <<EOF
:${PORT:-8080} {
    root * ${APP_DIR}/public
    php_fastcgi unix//run/php/php8.5-fpm.sock
    file_server
    encode gzip

    @static {
        path /build/*
        path /images/*
        path /favicon.ico
    }
    header @static Cache-Control "public, max-age=31536000, immutable"

    try_files {path} {path}/ /index.php?{query}
}
EOF

  touch "$APP_DIR/.build-done"
  echo "==> Build complete."

fi

# ── START PHASE (runs every restart) ─────────

echo "==> Starting PHP-FPM (daemonized)…"
php-fpm8.5 --daemonize

echo "==> Starting Caddy…"
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
