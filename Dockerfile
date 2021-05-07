FROM php:7.3
WORKDIR /app
ADD https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer /tmp
COPY / ./
RUN apt-get update &&\
    apt-get install zip unzip apt-utils git calibre -y &&\
    php /tmp/installer quiet &&\
    php composer.phar install &&\
    apt-get clean autoclean &&\
    apt-get autoremove --yes &&\
    rm -rf /var/lib/{apt,dpkg,cache,log}/
WORKDIR /app/output
ENTRYPOINT [ "/app/entrypoint.sh" ]
