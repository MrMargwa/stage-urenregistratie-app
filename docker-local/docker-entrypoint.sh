#!/bin/sh
set -e

# Als vendor leeg is of niet bestaat: composer install
if [ ! -d "/var/www/vendor" ] || [ -z "$(ls -A /var/www/vendor)" ]; then
    echo "Vendor directory empty, running composer install..."
    composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
fi

# Laravel permissions fix
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Start het commando (php artisan serve of php-fpm)
exec "$@"
