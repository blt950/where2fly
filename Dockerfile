# Intermediate build container for front-end resources
FROM docker.io/library/node:26.7-alpine AS frontend
# Easy to prune intermediary containers
LABEL stage=build

WORKDIR /app

# Lockfile first so the install layer caches independently of app source changes
COPY package.json package-lock.json /app/
RUN npm ci --omit dev

COPY ./ /app/

# SENTRY_RELEASE is read from config/app.php, so this must stay after the full COPY
RUN --mount=type=secret,id=sentry_auth_token \
    export SENTRY_AUTH_TOKEN="$(cat /run/secrets/sentry_auth_token 2>/dev/null || true)"; \
    export SENTRY_RELEASE="$(sed -n "s/.*'version' *=> *'\([^']*\)'.*/\1/p" config/app.php)"; \
    npx vite build

####################################################################################################
# Primary container
FROM docker.io/library/php:8.5.9-apache-trixie

# Default container port for the apache configuration
EXPOSE 80 443

# Install base dependencies (git is composer's fallback source driver)
RUN apt-get update && \
    apt-get install -y --no-install-recommends curl git unzip vim nano ca-certificates && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Oracle MySQL Client from Oracle's own .debs — apt resolves the runtime libs, and
# MYSQL_CLIENT_DISTRO must track the base image's Debian release (the URL 404s on mismatch).
ARG MYSQL_CLIENT_VERSION=8.4.9
ARG MYSQL_CLIENT_DISTRO=debian13
RUN set -eux; \
    cd /tmp; \
    for p in mysql-common mysql-community-client-plugins mysql-community-client-core; do \
        curl -fsSL "https://cdn.mysql.com/Downloads/MySQL-8.4/${p}_${MYSQL_CLIENT_VERSION}-1${MYSQL_CLIENT_DISTRO}_amd64.deb" -O; \
    done; \
    apt-get update; \
    apt-get install -y --no-install-recommends /tmp/mysql-*.deb; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/mysql-*.deb; \
    mysql --version; \
    mysqldump --version

# Enable required Apache modules
RUN a2enmod rewrite ssl remoteip

# Custom Apache2 configuration based on defaults; fairly straightforward
COPY ./container/configs/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./container/configs/apache.conf /etc/apache2/apache2.conf

# Install PHP extension(s) before the custom php.ini lands: excimer comes from PECL, and
# PEAR's OS_Guess needs popen(), which our php.ini adds to disable_functions.
COPY --from=mlocati/php-extension-installer:2.11.12 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip opcache intl excimer

# Custom PHP configuration based on $PHP_INI_DIR/php.ini-production
COPY ./container/configs/php.ini /usr/local/etc/php/php.ini

# Install composer
COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app

# Deps layer: no scripts (artisan isn't copied yet), no autoloader (dumped after the app COPY)
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# Copy over the application, static files, plus the ones built/transpiled by Vite in the frontend stage further up
COPY --chown=www-data:www-data ./ /app/
COPY --from=frontend --chown=www-data:www-data /app/public/build/ /app/public/build/

# Fires post-autoload-dump → package:discover, so no --no-scripts here
RUN composer dump-autoload --optimize --no-dev

# Normalise ownership/permissions of the writable trees before we drop into the service process.
RUN mkdir -p \
        /app/storage/logs \
        /app/storage/app/tmp \
        /app/storage/app/backup-temp \
        /app/storage/framework/cache \
        /app/storage/framework/sessions \
        /app/storage/framework/views \
        /app/bootstrap/cache && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R g+w /app/storage /app/bootstrap/cache

# Wrap around the default PHP entrypoint with a custom entrypoint
COPY ./container/entrypoint.sh /usr/local/bin/service-entrypoint
RUN chmod +x /usr/local/bin/service-entrypoint
ENTRYPOINT [ "service-entrypoint" ]
CMD ["apache2-foreground"]
