FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for Laravel & Supabase (PostgreSQL)
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Set proper permissions for Laravel storage/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Install dependencies WITHOUT executing artisan scripts during build
RUN composer install --no-dev --optimize-autoloader --no-scripts

EXPOSE 10000

# Run artisan commands on container boot after env variables are injected
CMD php artisan config:clear && php artisan package:discover --ansi && php artisan serve --host=0.0.0.0 --port=10000
