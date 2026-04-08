FROM php:8.2-cli

# Install system dependencies + PostgreSQL dev files
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip libpq-dev \
    && docker-php-ext-install zip pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure storage is writable (standard Laravel requirement)
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Combined CMD: It will try to migrate, but even if it fails, it will still start the server
CMD php artisan config:clear && php artisan migrate --force ; php artisan serve --host=0.0.0.0 --port=10000