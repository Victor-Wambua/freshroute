# NOT BUILT on the development machine (no Docker available). Reviewed by reading only.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --optimize-autoloader

FROM php:8.4-cli-alpine
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev libwebp-dev icu-dev postgresql-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install gd exif intl pdo_pgsql pdo_mysql opcache
WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN php artisan package:discover --ansi && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 8080
HEALTHCHECK CMD wget -qO- http://127.0.0.1:8080/health || exit 1
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
