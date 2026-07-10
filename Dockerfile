FROM php:8.4-cli

# System deps + Node.js 20
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libjpeg-dev libwebp-dev \
    libxml2-dev libonig-dev libicu-dev \
    default-mysql-client \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql mbstring zip xml bcmath intl exif gd pcntl \
    && rm -rf /var/lib/apt/lists/*

# PHP upload limits — match local dev settings
RUN echo "upload_max_filesize = 20M\npost_max_size = 21M" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

# Dependencies first (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

# Copy source
COPY . .

# Build assets
RUN npm run build

# Laravel bootstrap
RUN php artisan storage:link || true

EXPOSE 8000

CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force \
    && date -u +"%Y-%m-%d %H:%M:%S UTC" > DEPLOY_TIME \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
