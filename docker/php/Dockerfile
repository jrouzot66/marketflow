FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# System deps
RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    postgresql-dev \
    rabbitmq-c-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) \
    intl \
    opcache \
    pdo \
    pdo_pgsql \
    zip

RUN pecl install redis \
 && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/zz-marketflow.ini

RUN addgroup -g 1000 app && adduser -D -G app -u 1000 app
USER app

CMD ["php-fpm", "-F"]