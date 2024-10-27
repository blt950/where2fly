# Intermediate build container for front-end resources
FROM docker.io/library/node:22.5.1-alpine AS frontend
# Easy to prune intermediary containers
LABEL stage=build

WORKDIR /app
COPY ./ /app/

RUN npm ci --omit dev && \
    npx vite build

####################################################################################################
# Primary container

# Primary container
FROM docker.io/library/php:8.3.9-apache-bookworm

# Default container port for the apache configuration
EXPOSE 80 443

# Install various dependencies
# - git and unzip for composer
# - vim and nano for our egos
# - ca-certificates for OAuth2
RUN apt-get update && \
    apt-get install -y git unzip vim nano ca-certificates default-mysql-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite ssl remoteip


# Custom Apache2 configuration based on defaults; fairly straightforward
COPY ./container/configs/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./container/configs/apache.conf /etc/apache2/apache2.conf
# Custom PHP configuration based on $PHP_INI_DIR/php.ini-production
COPY ./container/configs/php.ini /usr/local/etc/php/php.ini

# Install PHP extension(s)
COPY --from=mlocati/php-extension-installer:2.6.1 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip

# Install composer
COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer
# Copy over the application, static files, plus the ones built/transpiled by Mix in the frontend stage further up
COPY --chown=www-data:www-data ./ /app/
COPY --from=frontend --chown=www-data:www-data /app/public/ /app/public/

WORKDIR /app

RUN composer install --no-dev --no-interaction --prefer-dist
RUN mkdir -p /app/storage/logs/

# Wrap around the default PHP entrypoint with a custom entrypoint
COPY ./container/entrypoint.sh /usr/local/bin/service-entrypoint
ENTRYPOINT [ "service-entrypoint" ]
CMD ["apache2-foreground"]
