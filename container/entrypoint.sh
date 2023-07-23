#!/bin/bash
set -e

SERVICE_ROOT=/app
SELF_SIGNED_KEY=/etc/ssl/private/apache-selfsigned.key
SELF_SIGNED_CERT=/etc/ssl/certs/apache-selfsigned.crt

if [ ! -f "$SELF_SIGNED_KEY" ] || [ ! -f "$SELF_SIGNED_CERT" ]; then
    # Generate a self-signed cert to support SSL connections
    openssl req -x509 -nodes -days 358000 -newkey rsa:2048 -keyout "$SELF_SIGNED_KEY" -out "$SELF_SIGNED_CERT" -subj "/O=Your vACC/CN=Stands"
fi

if [ -z "$APP_KEY" ] && [ ! -f "$SERVICE_ROOT/.env" ]; then
    cp container/default.env .env
    php artisan key:generate
fi

exec docker-php-entrypoint "$@"

