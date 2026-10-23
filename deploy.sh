#!/usr/bin/env bash
# ==============================================================================
# FleetPulse Production Deployment Script
# Adheres to Workflow Master Production Release Standards
# ==============================================================================

set -e

echo "🚀 [1/7] Entering Maintenance Mode..."
php artisan down || true

echo "📦 [2/7] Pulling Latest Production Code..."
git pull origin main

echo "🐘 [3/7] Installing PHP Dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "🗄️ [4/7] Running Database Migrations..."
php artisan migrate --force

echo "⚡ [5/7] Caching Configuration, Routes & Views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🎨 [6/7] Building Frontend Production Assets..."
npm ci
npm run build

echo "🔄 [7/7] Restarting Queue Workers & Exiting Maintenance Mode..."
php artisan queue:restart
php artisan up

echo "✅ FleetPulse successfully deployed to production!"
