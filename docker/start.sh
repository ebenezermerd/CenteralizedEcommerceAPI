#!/bin/bash
set -e

cd /var/www/html

# Ensure storage directory permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Start PHP-FPM
php-fpm &

# Wait for PHP-FPM to start
sleep 2

# Run Laravel commands
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php artisan migrate --force

# Start Nginx
nginx -g 'daemon off;'

