# -----------------------------------------------
# Stage 1: Build Frontend Assets (Vite + Tailwind)
# -----------------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /app

# Cache node modules layer
COPY package*.json ./
RUN npm ci

# Copy application source & compile production assets
COPY . .
RUN npm run build

# -----------------------------------------------
# Stage 2: Production PHP-FPM & Nginx Environment
# -----------------------------------------------
FROM php:8.4-fpm

# Install essential runtime dependencies only (no nodejs/npm in runtime)
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libonig-dev \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure & Install PHP extensions required by your composer.json
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl opcache

# Copy production OPcache and PHP settings (zz- prefix loads last, so it overrides defaults)
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-custom.ini

# Install official Composer executable
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Step A: Install composer dependencies without scripts/autoloader for layer caching
COPY composer.json composer.lock ./
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Step B: Copy application codebase
COPY . .

# Step C: Dump optimized autoloader (also runs package:discover via composer script)
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Step D: Copy compiled Vite build artifacts from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Copy runtime server configurations
COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Make sure Laravel's writable folders exist, cache routes and views,
# then fix permissions. config:cache runs at startup so Render's env vars are used.
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD ["/entrypoint.sh"]
