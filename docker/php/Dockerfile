FROM php:8.3-fpm AS php-base

ARG HOST_UID=1000
ARG HOST_GID=1000

RUN groupmod -o -g "${HOST_GID}" www-data \
    && usermod -o -u "${HOST_UID}" -g "${HOST_GID}" www-data

RUN set -eux; \
    if [ -f /etc/apt/sources.list ]; then \
    sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list; \
    fi; \
    if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
    sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list.d/debian.sources; \
    fi; \
    printf '%s\n' \
    'Acquire::Retries "8";' \
    'Acquire::ForceIPv4 "true";' \
    'Acquire::http::Timeout "30";' \
    'Acquire::https::Timeout "30";' \
    > /etc/apt/apt.conf.d/99-network-hardening; \
    for i in 1 2 3; do \
    apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev && break; \
    if [ "${i}" = "3" ]; then \
    exit 1; \
    fi; \
    echo "apt failed, retrying in $((i * 5))s..."; \
    sleep $((i * 5)); \
    rm -rf /var/lib/apt/lists/*; \
    done; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
    bcmath \
    gd \
    intl \
    mbstring \
    pdo_mysql \
    pdo_sqlite \
    zip; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM php-base AS composer-deps

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader

FROM composer-deps AS app-build

COPY . .
RUN cp .env.example .env \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && php artisan migrate

FROM node:22-alpine AS frontend-build

WORKDIR /var/www/html
ENV VITE_WAYFINDER_COMMAND="echo"

COPY package.json package-lock.json ./
RUN npm config set fetch-retries 8 \
    && npm config set fetch-retry-factor 2 \
    && npm config set fetch-retry-mintimeout 20000 \
    && npm config set fetch-retry-maxtimeout 120000 \
    && for i in 1 2 3; do \
    npm ci && break; \
    if [ "${i}" = "3" ]; then \
    exit 1; \
    fi; \
    echo "npm ci failed, retrying in $((i * 10))s..."; \
    sleep $((i * 10)); \
    npm cache verify; \
    done

COPY --from=app-build /var/www/html /var/www/html
RUN npm run build

FROM php-base AS app-runtime

WORKDIR /var/www/html

COPY --from=app-build /var/www/html /var/www/html
COPY --from=frontend-build /var/www/html/public/build /var/www/html/public/build

RUN rm -f /var/www/html/public/storage \
    && ln -s /var/www/html/storage/app/public /var/www/html/public/storage \
    && mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web-runtime

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app-runtime /var/www/html/public /var/www/html/public
