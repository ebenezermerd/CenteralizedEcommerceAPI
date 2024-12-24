release: php artisan migrate --force && php artisan optimize && php artisan view:cache
web: php artisan config:cache && php artisan route:cache && heroku-php-apache2 public/
worker: php artisan queue:work --tries=3 --queue=default
