#!/bin/sh
set -e

echo "--- Running entrypoint ---"

# Build frontend assets
if [ -f "package.json" ]; then
    echo "Building frontend assets..."
    npm run build
fi

# Cache config untuk performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migration otomatis
echo "Running migrations..."
php artisan migrate --force

# Seed hanya jika database kosong (cek tabel users)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

echo "--- Entrypoint done ---"

# Jalankan PHP-FPM
exec php-fpm