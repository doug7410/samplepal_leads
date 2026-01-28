#!/bin/sh
set -e

echo "Starting Laravel application..."

# Docker logging is already configured in the mounted .env file
# No need to modify it here

# Create .env file if it doesn't exist
if [ ! -f "/var/www/html/.env" ]; then
    echo "Creating .env file from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env || echo "No .env.example found, creating basic .env"

    # If no .env.example exists, create a basic one
    if [ ! -f "/var/www/html/.env" ]; then
        cat > /var/www/html/.env << 'EOF'
APP_NAME="SamplePal Leads"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

BROADCAST_CONNECTION=log
CACHE_STORE=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

VITE_APP_NAME="${APP_NAME}"
EOF
    fi
fi

# Wait for database to be ready (if using external database)
if [ ! -z "$DB_CONNECTION" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "Waiting for database connection..."
    while ! php artisan db:monitor > /dev/null 2>&1; do
        echo "Database is unavailable - sleeping"
        sleep 1
    done
    echo "Database is up - continuing..."
fi

# Generate application key if not set (check both env var and .env file)
APP_KEY_FROM_FILE=""
if [ -f "/var/www/html/.env" ]; then
    APP_KEY_FROM_FILE=$(grep "^APP_KEY=" /var/www/html/.env 2>/dev/null | cut -d'=' -f2- | tr -d '"' || echo "")
fi

if [ -z "$APP_KEY" ] && [ -z "$APP_KEY_FROM_FILE" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
elif [ -z "$APP_KEY_FROM_FILE" ] || [ "$APP_KEY_FROM_FILE" = "base64:CHANGEME" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Create SQLite database if it doesn't exist
if [ ! -f "/var/www/html/database/database.sqlite" ]; then
    echo "Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations if AUTO_MIGRATE is set or if database is empty
if [ "$AUTO_MIGRATE" = "true" ] || [ ! -s "/var/www/html/database/database.sqlite" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache

# Only cache views if views directory exists and has content
if [ -d "/var/www/html/resources/views" ] && [ "$(ls -A /var/www/html/resources/views 2>/dev/null)" ]; then
    php artisan view:cache || echo "Warning: View cache failed, continuing..."
else
    echo "Warning: Views directory empty or missing, skipping view cache"
fi

php artisan event:cache

# Create storage link if it doesn't exist
if [ ! -L "public/storage" ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# Ensure storage directory structure exists
echo "Setting up storage directories..."
mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public

# Set correct permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create log directories if they don't exist
mkdir -p /var/log/php /var/log/supervisor
chown www-data:www-data /var/log/php
touch /var/log/supervisor/supervisord.log

echo "Application ready!"
echo "Starting supervisord with command: $@" >&2

# Execute the main command
exec "$@"
