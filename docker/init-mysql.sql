-- Create database with proper collation and charset
CREATE DATABASE IF NOT EXISTS `koricha_ecommerce_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Use mysql_native_password to avoid issues with older php-mysql drivers
CREATE USER IF NOT EXISTS 'koricha'@'localhost' 
IDENTIFIED WITH mysql_native_password BY 'saddle';
CREATE USER IF NOT EXISTS 'koricha'@'%' 
IDENTIFIED WITH mysql_native_password BY 'saddle';

-- Grant privileges to both localhost and % for flexibility
GRANT ALL PRIVILEGES ON `koricha_ecommerce_db`.* TO 'koricha'@'localhost';
GRANT ALL PRIVILEGES ON `koricha_ecommerce_db`.* TO 'koricha'@'%';

-- Allow user to access information_schema for Laravel migrations
GRANT SELECT ON `information_schema`.* TO 'koricha'@'localhost';
GRANT SELECT ON `information_schema`.* TO 'koricha'@'%';

-- Ensure privileges are saved
FLUSH PRIVILEGES;

-- Set global variables for better performance with Laravel
SET GLOBAL time_zone = '+00:00';
SET GLOBAL character_set_server = 'utf8mb4';
SET GLOBAL collation_server = 'utf8mb4_unicode_ci';

