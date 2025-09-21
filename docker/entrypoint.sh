#!/bin/bash
set -e

echo "Starting Laravel Docker entrypoint..."

# Create necessary directories if they don't exist
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/public/build

# Check if Vite assets exist, if not try to build them
if [ ! -f "/var/www/html/public/build/manifest.json" ]; then
    echo "Vite manifest not found, checking if we can build assets..."
    if command -v npm &> /dev/null && [ -f "/var/www/html/package.json" ]; then
        echo "Building Vite assets..."
        npm run build
        echo "Vite assets built successfully"
    else
        echo "Warning: Cannot build assets. npm or package.json not found."
        echo "Assets should be pre-built during Docker image creation."
    fi
else
    echo "Vite assets found, skipping build."
fi

# Set proper ownership and permissions
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Fix permissions for the entire application
find /var/www/html -type f -exec chmod 644 {} \;
find /var/www/html -type d -exec chmod 755 {} \;

# Make sure storage and bootstrap/cache are writable
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "Permissions set successfully"

# Execute the main command
exec "$@"
