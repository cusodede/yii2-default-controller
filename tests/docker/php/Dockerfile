ARG PHP_VERSION
FROM --platform=linux/amd64 php:${PHP_VERSION}-cli-alpine

RUN apk add unzip zlib-dev libzip-dev libpq-dev

RUN docker-php-ext-install zip pcntl opcache pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /var/www

ENTRYPOINT ["sh", "tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]