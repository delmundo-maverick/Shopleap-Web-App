#!/bin/sh
set -e
cd /var/www

# Cache config at runtime so Render's env vars are used, not build-time ones
php artisan config:cache

chown -R www-data:www-data storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
