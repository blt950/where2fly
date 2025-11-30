# Intermediate build container for front-end resources
FROM docker.io/library/node:25.1.0-alpine AS frontend
# Easy to prune intermediary containers
LABEL stage=build

WORKDIR /app
COPY ./ /app/

RUN npm ci --omit dev && \
    npx vite build

####################################################################################################
# Primary container
FROM docker.io/library/php:8.3.28-apache-trixie

# Default container port for the apache configuration
EXPOSE 80 443

# Install base dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends curl xz-utils git unzip vim nano ca-certificates && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install runtime libs required by Oracle MySQL client
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libncurses6 \
        libtinfo6 \
        libzstd1 \
        zlib1g \
        libssl3 && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Oracle MySQL Client
ARG MYSQL_CLIENT_VERSION=8.4.2
RUN set -eux; \
    curl -fsSL "https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-${MYSQL_CLIENT_VERSION}-linux-glibc2.28-x86_64.tar.xz" -o /tmp/mysql-client.tar.xz; \
    tar -xf /tmp/mysql-client.tar.xz -C /usr/local; \
    mv "/usr/local/mysql-${MYSQL_CLIENT_VERSION}-linux-glibc2.28-x86_64" /usr/local/mysql; \
    ln -s /usr/local/mysql/bin/mysql /usr/local/bin/mysql; \
    ln -s /usr/local/mysql/bin/mysqldump /usr/local/bin/mysqldump; \
    mysql --version; \
    rm /tmp/mysql-client.tar.xz

# Enable required Apache modules
RUN a2enmod rewrite ssl remoteip

# Custom Apache2 configuration based on defaults; fairly straightforward
COPY ./container/configs/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./container/configs/apache.conf /etc/apache2/apache2.conf
# Custom PHP configuration based on $PHP_INI_DIR/php.ini-production
COPY ./container/configs/php.ini /usr/local/etc/php/php.ini

# Install PHP extension(s)
COPY --from=mlocati/php-extension-installer:2.9.13 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip opcache

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
