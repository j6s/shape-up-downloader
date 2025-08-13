FROM php:8.4-cli AS base

# Intermediate image that contains dev tools in order to build the application
FROM base AS builder

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY ./composer.json /app/composer.json
COPY ./composer.lock /app/composer.lock
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader

# The actual image that will be used to run the application
FROM base
RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

COPY --from=builder /app /app
COPY ./ /app

WORKDIR /app/output
ENTRYPOINT [ "/app/docker-entrypoint.sh" ]
