#!/bin/sh
set -e

echo "🚀 Starting deployment..."

# Never generate a temporary production key. Render's filesystem is ephemeral,
# so a generated key would change after a restart and encrypted data would break.
if [ -z "$APP_KEY" ]; then
    if [ "$APP_ENV" = "production" ]; then
        echo "❌ APP_KEY is required in production. Generate it once with: php artisan key:generate --show"
        exit 1
    fi

    echo "⚠️  APP_KEY not set, generating a local development key..."
    php artisan key:generate --force
fi

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Create the first admin when ADMIN_EMAIL and ADMIN_PASSWORD are configured.
# Existing admin passwords are never overwritten by this startup seed.
echo "🌱 Seeding Admin user..."
php artisan db:seed --class=AdminSeeder --force

# Seed Settings
echo "⚙️ Seeding Settings..."
php artisan db:seed --class=SettingSeeder --force

# Create storage link
php artisan storage:link --force 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ App ready! Starting services..."

# Start Supervisor (manages php-fpm, nginx, queue worker)
exec /usr/bin/supervisord -c /etc/supervisord.conf
