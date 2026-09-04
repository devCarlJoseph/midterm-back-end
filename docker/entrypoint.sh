#!/bin/sh
set -e

cd /var/www/html

# --- Development only: bind-mounted volume may be missing dependencies -------
if [ "$APP_ENV" != "production" ]; then
    if [ ! -f vendor/autoload.php ]; then
        echo "[entrypoint] Installing composer dependencies..."
        composer install --no-interaction --prefer-dist
    fi

    # Generate an app key into the (bind-mounted) .env if it has none.
    if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
        echo "[entrypoint] Generating APP_KEY..."
        php artisan key:generate --force || true
    fi
fi

# --- Database migrations (db is already healthy via depends_on) -------------
echo "[entrypoint] Running migrations..."
php artisan migrate --force || true

# --- Optimize for production / keep fresh for dev ---------------------------
if [ "$APP_ENV" = "production" ]; then
    echo "[entrypoint] Caching config, routes and views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    php artisan optimize:clear || true
fi

php artisan storage:link || true

echo "[entrypoint] Starting: $*"
exec "$@"