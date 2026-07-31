FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p database && touch database/database.sqlite

EXPOSE 8000

# artisan serve is fine for the small, low-traffic OTP endpoint this project
# targets; swap for php-fpm + nginx if you need to serve real traffic.
#
# --no-reload is required: by default `serve` only forwards a small allowlist
# of env vars (APP_ENV, PATH, ...) to the actual request-handling process,
# reloading it from the .env file on disk for everything else (it's built to
# pick up .env edits live). We configure this app entirely through
# docker-compose `environment:` vars, not a baked-in .env file, so without
# --no-reload, WA_ENGINE_URL/ENGINE_INTERNAL_SECRET/etc. would silently never
# reach real requests and every config value would fall back to its default.
CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan serve --no-reload --host=0.0.0.0 --port=8000"]
