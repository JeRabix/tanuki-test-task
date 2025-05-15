ARG PHP_VERSION=8.3
ARG SCHEDULE_RUN

FROM php:${PHP_VERSION}-fpm-alpine

#####################################
# System:
#####################################

# Set UTC timezone
RUN echo "UTC" > /etc/timezone

#####################################
# Software:
#####################################

# Install php dependencies
RUN apk add --no-cache --virtual .phpize-deps  \
            ${PHPIZE_DEPS} \
            dos2unix

# Update linux-headers - require xdebug
RUN apk add --no-cache --update linux-headers

#####################################
# PHP Extensions:
#####################################

# Install MySQL extension
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-enable pdo_mysql

# Install OPcache extension
RUN docker-php-ext-install opcache
RUN docker-php-ext-enable opcache

# Install xDebug extension
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Install pcntl extension
RUN docker-php-ext-install pcntl
RUN docker-php-ext-enable pcntl

# Install GD extension
RUN apk add libpng-dev
RUN docker-php-ext-install gd
RUN docker-php-ext-enable gd

#####################################
# Composer:
#####################################
RUN (curl -s https://getcomposer.org/installer | php)
RUN mv composer.phar /usr/local/bin/composer

#####################################
# Aliases:
#####################################
# docker-compose exec app artisan --> php artisan
RUN echo -e '#!/bin/sh\n/usr/local/bin/php /var/www/html/artisan "$@"' > /usr/bin/artisan
RUN chmod +x /usr/bin/artisan

# docker-compose exec app migrate --> php artisan migrate
RUN echo -e '#!/bin/sh\n/usr/local/bin/php /var/www/html/artisan migrate "$@"' > /usr/bin/migrate
RUN chmod +x /usr/bin/migrate

#####################################
# ENTRYPOINT:
#####################################
COPY docker/php/docker-entrypoint.sh /usr/local/bin/
RUN dos2unix /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

#####################################
# Clean:
#####################################
RUN apk del .phpize-deps
RUN rm -rf "/etc/apk/cache"
RUN pecl clear-cache

#####################################
# Final:
#####################################

# Configuration php
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/php/php-fpm.d/www.conf "$PHP_INI_DIR/../php-fpm.d/zz-www.conf"

WORKDIR /var/www/html/

# Add custom ps1
ENV PS1='\[\033[0;32m\]\u@\h:\[\033[36m\]\w\[\033[0m\] \$ '

ENTRYPOINT ["docker-entrypoint.sh"]
