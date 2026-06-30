#!/usr/bin/env bash
#
# deploy.sh — run this ON THE SERVER from the project root.
# First-time setup and subsequent deploys both use this script.
#
# Usage: ./deploy/deploy.sh

set -e

echo "==> Pulling latest code"
git pull origin main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Checking .env"
if [ ! -f .env ]; then
    echo "ERROR: .env not found. Copy .env.example to .env and fill in real credentials first."
    exit 1
fi

if ! grep -q "^APP_KEY=base64" .env; then
    echo "==> Generating APP_KEY"
    php artisan key:generate --force
fi

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Caching config/routes/views for production"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restarting queue workers (picks up new code)"
php artisan queue:restart

echo "==> Setting storage permissions"
chmod -R 775 storage bootstrap/cache

echo "==> Deploy complete."
echo ""
echo "Reminders:"
echo "  - Confirm the WhatsApp webhook URL in Meta's dashboard points to:"
echo "    https://yourdomain.com/api/webhooks/whatsapp"
echo "  - Confirm supervisor is running the queue worker (see deploy/supervisor-queue.conf)"
echo "  - Confirm the cron entry is active (see deploy/crontab.txt)"
