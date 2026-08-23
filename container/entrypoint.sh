#!/bin/bash
set -e

SERVICE_ROOT=/app
SELF_SIGNED_KEY=/etc/ssl/private/apache-selfsigned.key
SELF_SIGNED_CERT=/etc/ssl/certs/apache-selfsigned.crt

if [ ! -f "$SELF_SIGNED_KEY" ] || [ ! -f "$SELF_SIGNED_CERT" ]; then
    # Generate a self-signed cert to support SSL connections
    openssl req -x509 -nodes -days 358000 -newkey rsa:2048 -keyout "$SELF_SIGNED_KEY" -out "$SELF_SIGNED_CERT" -subj "/O=Your vACC/CN=Stands"
fi

# Normalise ownership/permissions of the writable trees before we drop into the service process.
mkdir -p \
    "$SERVICE_ROOT"/storage/logs \
    "$SERVICE_ROOT"/storage/app/tmp \
    "$SERVICE_ROOT"/storage/app/backup-temp \
    "$SERVICE_ROOT"/storage/framework/cache \
    "$SERVICE_ROOT"/storage/framework/sessions \
    "$SERVICE_ROOT"/storage/framework/views \
    "$SERVICE_ROOT"/bootstrap/cache
chown -R www-data:www-data "$SERVICE_ROOT"/storage "$SERVICE_ROOT"/bootstrap/cache
chmod -R g+w "$SERVICE_ROOT"/storage "$SERVICE_ROOT"/bootstrap/cache

if [ -z "$APP_KEY" ] && [ ! -f "$SERVICE_ROOT/.env" ]; then
    cp container/default.env .env
    php artisan key:generate
fi

# Build config/route/view caches at runtime (they bake in the final .env),
# then hand the root-owned cache files back to the service user.
php artisan optimize
chown -R www-data:www-data "$SERVICE_ROOT"/bootstrap/cache "$SERVICE_ROOT"/storage/framework/views

exec docker-php-entrypoint "$@"

