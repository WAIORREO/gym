#!/bin/sh
set -e

echo "=== LaraGym Container Starting ==="

# Ensure storage directories exist
mkdir -p /var/www/database /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache
chmod -R 777 /var/www/storage

# If PostgreSQL env vars are provided, update .env
if [ "$DB_CONNECTION" = "pgsql" ] || [ -n "$DATABASE_URL" ]; then
    echo "Configuring PostgreSQL connection in .env..."
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" /var/www/.env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-laragym_j4td}|" /var/www/.env
    
    sed -i '/^DB_HOST=/d' /var/www/.env
    sed -i '/^DB_PORT=/d' /var/www/.env
    sed -i '/^DB_USERNAME=/d' /var/www/.env
    sed -i '/^DB_PASSWORD=/d' /var/www/.env
    sed -i '/^DATABASE_URL=/d' /var/www/.env
    sed -i '/^MAIL_MAILER=/d' /var/www/.env

    echo "DB_HOST=${DB_HOST:-dpg-db8ib9ui0phs73fpf0jg-a}" >> /var/www/.env
    echo "DB_PORT=${DB_PORT:-5432}" >> /var/www/.env
    echo "DB_USERNAME=${DB_USERNAME:-laragym}" >> /var/www/.env
    echo "DB_PASSWORD=${DB_PASSWORD:-ijIciReAOFdaV4qcqsIZMwqop9G8uEsS}" >> /var/www/.env
    echo "DATABASE_URL=${DATABASE_URL}" >> /var/www/.env
    echo "MAIL_MAILER=log" >> /var/www/.env
else
    touch /var/www/database/database.sqlite
    chmod 777 /var/www/database/database.sqlite
fi

php artisan config:clear || true
php artisan cache:clear || true

echo "Running migrations..."
php artisan migrate --force

echo "Running database seeder..."
php artisan db:seed --force

echo "Linking storage..."
php artisan storage:link || true

PORT_NUMBER=${PORT:-8000}
echo "Starting Laravel server on 0.0.0.0:$PORT_NUMBER..."
exec php artisan serve --host=0.0.0.0 --port=$PORT_NUMBER
