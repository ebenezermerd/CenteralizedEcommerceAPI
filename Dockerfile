# Stage 1: Composer dependencies
FROM composer:2 as composer

WORKDIR /app

# Copy only dependency files first
COPY composer.json composer.lock ./

# Install dependencies with proper flags
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --optimize-autoloader

# Stage 2: Production image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libmcrypt-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmagickwand-dev \
    libmemcached-dev \
    libssl-dev \
    libwebp-dev \
    zip \
    unzip \
    supervisor \
    git \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    intl \
    && docker-php-ext-enable opcache

# Configure PHP
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Configure nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Configure supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .
COPY --from=composer /app/vendor ./vendor

# Copy environment file
COPY .env.production .env

# Set file permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache && \
    chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Create storage link
RUN php artisan storage:link

# Optimize Laravel for production
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    composer dump-autoload --optimize

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
