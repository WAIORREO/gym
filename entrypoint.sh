#!/bin/sh
set -e

# Run migrations and seed default data
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true

# Start server on Render's dynamic PORT
PORT_NUMBER=${PORT:-8000}
echo "Starting Laravel server on port $PORT_NUMBER..."
exec php artisan serve --host=0.0.0.0 --port=$PORT_NUMBER
