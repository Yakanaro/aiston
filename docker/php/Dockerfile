FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash git curl libpq-dev libzip-dev oniguruma-dev icu-dev autoconf g++ make \
 && docker-php-ext-install pdo pdo_pgsql mbstring zip intl \
 && apk del autoconf g++ make || true

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]


