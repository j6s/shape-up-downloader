FROM composer as composer_install

COPY ./composer.json /app/composer.json
COPY ./composer.lock /app/composer.lock
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader

FROM php:8.0
WORKDIR /app

COPY --from=composer_install /app /app
COPY ./ /app

WORKDIR /app/output
ENTRYPOINT [ "/app/docker-entrypoint.sh" ]
