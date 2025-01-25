#!/bin/bash
set -e

# Function to log messages with severity
log() {
    local severity=$1
    local message=$2
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] [${severity}] ${message}"
}

# Function to log error and exit
error_exit() {
    log "ERROR" "$1"
    exit 1
}

# Function to wait for MySQL to be ready
wait_for_mysql() {
    local max_tries=60
    local password_arg=""
    if [ -n "${MYSQL_ROOT_PASSWORD}" ]; then
        password_arg="-p${MYSQL_ROOT_PASSWORD}"
    fi
    
    until mysql -u root ${password_arg} -e "SELECT 1" >/dev/null 2>&1; do
        max_tries=$((max_tries - 1))
        if [ $max_tries -le 0 ]; then
            error_exit "MySQL did not become ready in time"
        fi
        log "INFO" "Waiting for MySQL to be ready... ($max_tries attempts left)"
        sleep 2
    done
}

# Set up Laravel directories
log "INFO" "Setting up Laravel directories..."
cd /var/www/html
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Initialize MySQL if needed
if [ ! -d "/var/lib/mysql/mysql" ]; then
    log "INFO" "Initializing MySQL data directory..."
    
    # Initialize MySQL with temporary root password
    mysqld --initialize-insecure --user=mysql || error_exit "MySQL initialization failed"
    
    # Generate and secure init file
    cat > /tmp/init.sql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
EOF
    
    chmod 600 /tmp/init.sql
    chown mysql:mysql /tmp/init.sql
    
    # Start MySQL in background with init file
    mysqld --user=mysql --init-file=/tmp/init.sql &
    MYSQL_PID=$!
    
    # Wait for MySQL to be ready
    log "INFO" "Waiting for MySQL to initialize..."
    if ! wait_for_mysql; then
        kill $MYSQL_PID
        error_exit "MySQL initialization failed"
    fi
    
    # Clean up
    rm -f /tmp/init.sql
    log "INFO" "MySQL initialization completed successfully"
fi

# Start MySQL for normal operation
log "INFO" "Starting MySQL..."
/usr/sbin/mysqld --user=mysql --default-authentication-plugin=mysql_native_password &
MYSQL_PID=$!

# Wait for MySQL to be ready
if ! wait_for_mysql; then
    kill $MYSQL_PID
    error_exit "MySQL failed to start"
fi
log "INFO" "MySQL is ready"

# Start PHP-FPM
log "INFO" "Starting PHP-FPM..."
php-fpm -D || error_exit "Failed to start PHP-FPM"

# Start Nginx
log "INFO" "Starting Nginx..."
nginx || error_exit "Failed to start Nginx"

# Configure Laravel for production
log "INFO" "Optimizing Laravel for production..."
php artisan config:cache || error_exit "Failed to cache Laravel config"
php artisan route:cache || error_exit "Failed to cache Laravel routes"
php artisan view:cache || error_exit "Failed to cache Laravel views"
php artisan migrate --force || error_exit "Failed to run migrations"

# Clean up temp files
log "INFO" "Cleaning up temporary files..."
rm -rf /tmp/*

# Start the supervisor daemon
log "INFO" "Starting supervisor daemon..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf || error_exit "Failed to start Supervisor"

