FROM php:8.2-cli-alpine

RUN apk add --no-cache \
        icu-dev \
        oniguruma-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" intl pdo pdo_mysql opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .

# symfony/runtime exige scripts do Composer (POST_AUTOLOAD_DUMP / post-install)
RUN mkdir -p var/cache var/log \
    && APP_ENV=prod \
       APP_SECRET=DockerBuildOnlySecret \
       DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4" \
       REDIS_URL="redis://127.0.0.1:6379" \
       MESSENGER_TRANSPORT_DSN="redis://127.0.0.1:6379/messages" \
       APP_URL="http://localhost" \
       STORAGE_API_URL="http://localhost" \
       STORAGE_SERVICE_TOKEN_SECRET="build" \
       AUTH_TOKEN_SALT="docker-build-auth-token-salt-32chars" \
       MERCADOPAGO_ACCESS_TOKEN="build" \
       MERCADOPAGO_WEBHOOK_SECRET="build" \
       MAILER_DSN="null://null" \
       CORS_ALLOW_ORIGIN='^https?://.*$' \
       DEFAULT_URI="http://localhost" \
       composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY docker/entrypoint.sh docker/start-web.sh docker/start-worker.sh docker/pre-deploy.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/start-web.sh /usr/local/bin/start-worker.sh /usr/local/bin/pre-deploy.sh

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/local/bin/start-web.sh"]
