FROM php:8.3-fpm

RUN apt-get update && apt-get install -y libpq-dev unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && pecl install apcu redis \
    && docker-php-ext-enable apcu redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

WORKDIR /var/www/html
