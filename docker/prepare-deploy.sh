#!/bin/sh
set -eu

php artisan config:clear --no-interaction
php artisan migrate --force --isolated=1 --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
