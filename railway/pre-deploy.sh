#!/bin/sh
set -e

# Zorg dat het nginx-loggingspad bestaat zodat nginx kan opstarten
mkdir -p /var/log/nginx

echo "Waiting for database to become available..."
MAX_ATTEMPTS=30
attempt=0

until php artisan migrate --force --no-interaction 2>/dev/null; do
    attempt=$((attempt + 1))

    if [ "$attempt" -ge "$MAX_ATTEMPTS" ]; then
        echo "Database did not become available within $MAX_ATTEMPTS attempts."
        exit 1
    fi

    echo "Database not ready yet, retrying in 5s (attempt $attempt/$MAX_ATTEMPTS)..."
    sleep 5
done

echo "Migrations complete."

# De seeder is idempotent: hij maakt de admin-account alleen aan als die
# ontbreekt en wijzigt bestaande gegevens nooit (ook een online gewijzigd
# wachtwoord blijft gewoon bewaard en wordt niet hersteld).
echo "Seeding default admin-account (no-op als die al bestaat)..."
php artisan db:seed --force --no-interaction

echo "Caching config, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Pre-deploy finished."