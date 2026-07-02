# ─── Stage 1: Build ───────────────────────────────────────────────
FROM php:8.4-cli AS build

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libjpeg-dev libwebp-dev \
    libxml2-dev libonig-dev libicu-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql mbstring zip xml bcmath intl exif gd pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1

# Dependencies first (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

# Copy source and build assets
COPY . .
RUN npm run build

# ─── Stage 2: Runtime ─────────────────────────────────────────────
FROM php:8.4-apache

# Runtime PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev libwebp-dev \
    libxml2-dev libonig-dev libicu-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql mbstring zip xml bcmath intl exif gd pcntl \
    && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict: ensure ONLY mpm_prefork is loaded
RUN rm -f /etc/apache2/mods-enabled/mpm_*.conf /etc/apache2/mods-enabled/mpm_*.load \
    && (sed -i '/^LoadModule mpm_/d' /etc/apache2/apache2.conf 2>/dev/null || true) \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Copy built app from build stage
COPY --from=build /app /app

WORKDIR /app

# Point Apache at Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/security.conf \
        /etc/apache2/conf-available/docker-php.conf

# Fix storage permissions
RUN mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/
ENTRYPOINT ["docker-entrypoint.sh"]
