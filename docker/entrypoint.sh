#!/bin/sh
set -e
cd /var/www

sh /var/www/docker/prepare-deploy.sh

chown -R www-data:www-data storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
