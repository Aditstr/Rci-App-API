#!/bin/sh
set -e

echo "🚀 Starting deployment..."

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "⚠️  APP_KEY not set, generating..."
    php artisan key:generate --force
fi

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Create storage link
php artisan storage:link --force 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ App ready! Starting services..."

# Start Supervisor (manages php-fpm, nginx, queue worker)
exec /usr/bin/supervisord -c /etc/supervisord.conf
