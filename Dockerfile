# Stage 1: Composer dependencies
FROM composer:2 as composer
WORKDIR /app

# Copy only dependency files first
COPY composer.json composer.lock ./

# Install dependencies with proper flags
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --optimize-autoloader

# Stage 2: Production image
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Configure nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files first
COPY . .

# Copy vendor files from composer stage
COPY --from=composer /app/vendor ./vendor

# Copy example env file and optimize
RUN cp .env.example .env && \
    composer dump-autoload --optimize && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Create storage directories and set permissions
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 storage bootstrap/cache && \
    chmod -R ugo+rw storage/logs

# Expose port
EXPOSE 80

# Start script
COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

CMD ["/usr/local/bin/start"]

