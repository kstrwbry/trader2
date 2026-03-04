FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl zip pdo_mysql pdo_pgsql opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html
