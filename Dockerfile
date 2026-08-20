FROM php:8.2-cli-alpine

RUN apk add --no-cache \
        icu-dev \
        oniguruma-dev \
    && apk add --no-cache --virtual .redis-build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install -j"$(nproc)" intl mbstring pdo pdo_mysql opcache \
    && apk del .redis-build-deps

COPY docker/php-railway.ini /usr/local/etc/php/conf.d/99-railway.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .

# symfony/runtime gera vendor/autoload_runtime.php no POST_AUTOLOAD_DUMP (plugins ativos)
RUN mkdir -p var/cache var/log \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && composer dump-autoload --optimize --classmap-authoritative --no-dev

COPY docker/entrypoint.sh docker/start-web.sh docker/start-worker.sh docker/pre-deploy.sh docker/migrate.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/start-web.sh /usr/local/bin/start-worker.sh /usr/local/bin/pre-deploy.sh /usr/local/bin/migrate.sh

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/local/bin/start-web.sh"]
