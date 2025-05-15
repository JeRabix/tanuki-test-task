#!/bin/sh

cd /var/www/html/ || exit

php artisan migrate --force
php artisan config:cache
php artisan route:cache

php-fpm
