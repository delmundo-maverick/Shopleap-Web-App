#!/bin/sh
set -e
cd /var/www

# Cache at runtime so Render's env vars are available.
# "|| echo" means a caching failure is logged but won't stop the app from starting.
php artisan config:cache || echo "WARNING: config:cache failed"
php artisan route:cache  || echo "WARNING: route:cache failed (closure routes?)"
php artisan view:cache   || echo "WARNING: view:cache failed"

chown -R www-data:www-data storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
