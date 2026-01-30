#!/bin/bash

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration and routes for performance
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache in the foreground
echo "Starting Apache..."
apache2-foreground
