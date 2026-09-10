FROM php:8.2-fpm

# Install system dependencies and PHP extensions for Laravel & Postgres/Supabase
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port and start Laravel artisan serve
EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000
