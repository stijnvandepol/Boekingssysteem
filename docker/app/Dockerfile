FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        mysql-client \
        su-exec \
        autoconf \
        gcc \
        g++ \
        make \
    && docker-php-ext-install \
        bcmath \
        intl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY docker/app/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/app/zz-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN addgroup -g 1000 app && adduser -D -G app -u 1000 app

COPY . /var/www

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && mkdir -p /var/log/php-fpm \
    && chown -R app:app /var/www/storage /var/www/bootstrap/cache /var/log/php-fpm

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
