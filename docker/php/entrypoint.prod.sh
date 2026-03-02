#!/bin/sh
# Cache config/route/view untuk performance production
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec "$@"
