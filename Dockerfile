# Stage 1: Composer dependencies
FROM composer:2 as composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

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

# Copy composer dependencies
COPY --from=composer /app/vendor ./vendor

# Copy application files
COPY . .

# Copy example env file
RUN cp .env.example .env

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Start script
COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

CMD ["/usr/local/bin/start"]

