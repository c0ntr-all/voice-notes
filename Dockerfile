# syntax=docker/dockerfile:1

# Локальный стек MVP.
# php    - PHP 8.4 + Apache, под Laravel 13. Как на обычном хостинге: public/, .htaccess, без воркеров.
# quasar - Node 24 LTS и Quasar CLI. Сборка Android (Cordova/Capacitor) остаётся на хосте: нужен Android SDK.

FROM php:8.4-apache-bookworm AS php

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        pdo_mysql \
        zip \
    && a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-voice-notes.ini
COPY docker/php/placeholder.php /opt/voice-notes-placeholder/index.php
COPY docker/php/entrypoint.sh /usr/local/bin/voice-notes-entrypoint.sh

RUN chmod +x /usr/local/bin/voice-notes-entrypoint.sh

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["voice-notes-entrypoint.sh"]
CMD ["apache2-foreground"]

FROM node:24-bookworm AS quasar

RUN npm install -g @quasar/cli@latest

COPY docker/quasar/entrypoint.sh /usr/local/bin/quasar-entrypoint.sh

RUN chmod +x /usr/local/bin/quasar-entrypoint.sh

WORKDIR /app

EXPOSE 9000

ENTRYPOINT ["quasar-entrypoint.sh"]
CMD ["quasar", "dev", "--hostname", "0.0.0.0", "--port", "9000"]
