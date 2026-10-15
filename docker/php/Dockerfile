FROM composer:2 AS composer
FROM php:8.4-fpm-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
    cron git unzip libonig-dev libxml2-dev util-linux \
    && docker-php-ext-install pdo_mysql mbstring dom xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install redis-6.3.0 && docker-php-ext-enable redis

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/blog.ini
COPY docker/php/cron/views-worker /etc/cron.d/views-worker
RUN chmod 0644 /etc/cron.d/views-worker
ENV TZ=UTC
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress
COPY . .
RUN mkdir -p storage/smarty storage/logs \
    && chown -R www-data:www-data storage

CMD ["php-fpm"]
