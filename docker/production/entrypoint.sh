#!/bin/sh
set -e

# Ensure Laravel storage and cache directories exist
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/backups
mkdir -p /var/www/html/bootstrap/cache

# Adjust ownership and permissions for web server
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink if not already linked
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

# Sync compiled assets to shared public volume if mounted
if [ -d /var/www/html/public-shared ]; then
    cp -r /var/www/html/public/. /var/www/html/public-shared/ 2>/dev/null || true
fi

# Execute CMD
exec "$@"
