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

# Admin-account alleen aanmaken/bijwerken bij eerste opzet (RUN_SEED=true).
# Daarna NIET meer seeden — je wilt je bestaande data (uren, accounts) intact houden.
# De UsersSeeder is idempotent en raakt time_entries niet aan, maar zodra de site
# live draait is seeden niet meer nodig.
if [ "${RUN_SEED:-false}" = "true" ]; then
    echo "RUN_SEED=true gevonden => database seeden (admin + testaccount)..."
    php artisan db:seed --force --no-interaction
else
    echo "RUN_SEED niet op true => geen seeding bij deze deploy (bestaande data blijft intact)."
fi

echo "Caching config, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Pre-deploy finished."
