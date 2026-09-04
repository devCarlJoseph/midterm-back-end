# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base — shared PHP-FPM runtime + extensions + composer + entrypoint
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libicu-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# ---------------------------------------------------------------------------
# vendor — production dependencies only (cached layer)
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ---------------------------------------------------------------------------
# development — code is bind-mounted at runtime; deps installed by entrypoint
# ---------------------------------------------------------------------------
FROM base AS development

COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/laravel.ini

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# production — self-contained image with code + vendor baked in
# ---------------------------------------------------------------------------
FROM base AS production

COPY docker/php/php.ini /usr/local/etc/php/conf.d/laravel.ini

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# nginx — serves static assets + proxies PHP to the app container.
# Rebuilt on every deploy so public/ never goes stale.
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public