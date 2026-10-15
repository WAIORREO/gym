#!/bin/sh
set -e

echo "=== LaraGym Container Starting ==="

# Ensure directories and database file exist
mkdir -p /var/www/database /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache
touch /var/www/database/database.sqlite
chmod -R 777 /var/www/database /var/www/storage

echo "Running migrations..."
php artisan migrate --force

echo "Running database seeder..."
php artisan db:seed --force

echo "Linking storage..."
php artisan storage:link || true

PORT_NUMBER=${PORT:-8000}
echo "Starting Laravel server on 0.0.0.0:$PORT_NUMBER..."
exec php artisan serve --host=0.0.0.0 --port=$PORT_NUMBER
