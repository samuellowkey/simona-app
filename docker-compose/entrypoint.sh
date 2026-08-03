#!/bin/sh
set -e

echo "--- Running entrypoint ---"

# Dynamic port binding for Railway or similar platforms
if [ -n "$PORT" ]; then
    echo "Configuring Nginx to listen on port $PORT..."
    if [ -f /etc/nginx/http.d/app.conf ]; then
        sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/http.d/app.conf
        sed -i "s/listen \[::\]:80;/listen \[::\]:${PORT};/g" /etc/nginx/http.d/app.conf
    elif [ -f /etc/nginx/conf.d/default.conf ]; then
        sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/conf.d/default.conf
        sed -i "s/listen \[::\]:80;/listen \[::\]:${PORT};/g" /etc/nginx/conf.d/default.conf
    else
        echo "Warning: Nginx configuration file not found, skipping port substitution."
    fi
fi

# Build assets dynamically in development if node is present
if [ "${APP_ENV:-production}" = "local" ] && [ -f "package.json" ] && command -v npm >/dev/null 2>&1; then
    echo "Building frontend assets for development..."
    npm run build
fi

# CLEAR ALL CACHE FIRST ON STARTUP (Agar view & route lama terhapus total)
echo "Clearing stale view and framework cache..."
php artisan view:clear
php artisan cache:clear

# Cache config & route only for production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Caching configuration and routes for production..."
    php artisan config:cache
    php artisan route:cache
    # php artisan view:cache  <-- KITA MATIKAN INI BIAR VIEW SELALU FRESH!
else
    echo "Clearing cached configuration for local development..."
    php artisan config:clear
    php artisan route:clear
fi

# Wait for database, run migrations, and seed
if [ "${APP_ENV:-production}" != "production" ] || [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    if [ -n "$DB_HOST" ]; then
        echo "Waiting for database connection ($DB_HOST:$DB_PORT)..."
        for i in $(seq 1 30); do
            if php -r "
                try {
                    \$host = getenv('DB_HOST') ?: '127.0.0.1';
                    \$port = getenv('DB_PORT') ?: '5432';
                    \$db   = getenv('DB_DATABASE') ?: 'forge';
                    \$user = getenv('DB_USERNAME') ?: 'forge';
                    \$pass = getenv('DB_PASSWORD') ?: '';
                    new PDO(\"pgsql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    exit(0);
                } catch (Exception \$e) {
                    fwrite(STDERR, \$e->getMessage() . \"\n\");
                    exit(1);
                }
            " >/dev/null 2>&1; then
                echo "Database is ready!"
                break
            fi
            echo "Database not ready yet (attempt $i/30)..."
            sleep 2
        done
    fi

    echo "Running migrations..."
    php artisan migrate --force

    echo "Syncing roles, permissions, and admin accounts..."
    php artisan db:seed --class=RoleAndPermissionSeeder --force
fi

echo "--- Entrypoint done ---"

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground to keep container running and output logs
echo "Starting Nginx..."
exec nginx -g "daemon off;"