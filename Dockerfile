FROM dunglas/frankenphp:php8.4 AS php-base
RUN install-php-extensions pdo_mysql pdo_pgsql pdo_sqlite redis pcntl gd intl zip bcmath opcache exif \
    && apt-get update \
    && apt-get install -y --no-install-recommends chromium nodejs npm \
    && rm -rf /var/lib/apt/lists/*
ENV PUPPETEER_SKIP_DOWNLOAD=true
RUN npm install --global puppeteer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

FROM php-base AS dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

FROM node:22-alpine AS assets
WORKDIR /build
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
COPY --from=dependencies /app/vendor ./vendor
RUN npm run build

FROM php-base AS app
COPY . .
COPY --from=dependencies /app/vendor ./vendor
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -s /app/storage/app/public /app/public/storage
COPY --from=assets /build/public/build ./public/build
ENV SERVER_NAME=:80 \
    SERVER_ROOT=/app/public \
    NODE_PATH=/usr/local/lib/node_modules \
    CHROME_PATH=/usr/bin/chromium \
    CHROME_NO_SANDBOX=true
EXPOSE 80
