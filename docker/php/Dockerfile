FROM php:8.5-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip libcurl4-openssl-dev libfreetype6-dev \
        libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev \
        libpq-dev libxml2-dev libzip-dev passwd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath exif gd intl pcntl \
        pdo_pgsql pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* \
    && groupmod -o -g "$GID" www-data \
    && usermod -o -u "$UID" -g "$GID" www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /var/www/backend

RUN mkdir -p /tmp/composer && chown -R www-data:www-data /tmp/composer

USER www-data

CMD ["php-fpm"]
