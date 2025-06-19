#!/usr/bin/env bash
set -e

# If vendor directory is missing, install dependencies
if [ ! -d "./vendor" ]; then
    echo "Installing PHP dependencies via Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ensure var directories exist and are writable by www-data
mkdir -p var/cache var/log
chown -R www-data:www-data var

# Execute the CMD (php-fpm)
exec "$@"
