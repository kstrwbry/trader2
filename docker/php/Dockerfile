FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    cron git unzip libicu-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl zip pdo_mysql pdo_pgsql opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html

# Copy crontab file
COPY cron-symfony /etc/cron.d/cron-symfony

# Set permissions and apply cron
RUN chmod 0644 /etc/cron.d/cron-symfony
RUN crontab /etc/cron.d/cron-symfony

# Create log file
RUN touch /var/www/html/cron.log

# Start cron in foreground when container runs
CMD ["cron", "-f"]
