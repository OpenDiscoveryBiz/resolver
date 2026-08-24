FROM dunglas/frankenphp:1-php8.5

WORKDIR /app

RUN install-php-extensions pcntl pdo_sqlite zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
RUN cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php

EXPOSE 8000

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--max-requests=500"]

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
