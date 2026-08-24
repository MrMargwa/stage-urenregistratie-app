FROM php:8.4-fpm

# Systeem dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev libzip-dev zip curl libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && rm -rf /var/lib/apt/lists/*

# Composer installeren
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Working directory
WORKDIR /var/www

# Daarna de rest van de app code
COPY . .

# Composer caching: eerst alleen de json files copy'en
COPY composer.json composer.lock ./

# Composer install met cache
# RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# Laravel permissions fix
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Entry point script
COPY docker-local/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Container start via entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]