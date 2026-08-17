# syntax=docker/dockerfile:1.7

# ---------- Stage 1: dependencias PHP (sin dev) ----------
FROM composer:2 AS vendor

WORKDIR /app

# Solo los manifiestos primero: maximiza el cache hit de esta capa.
COPY composer.json composer.lock ./

# --no-scripts evita que artisan corra sin el código fuente completo.
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ---------- Stage 2: assets front (Vite) ----------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Tailwind v4 / Livewire escanean blades y PHP: se necesita el fuente completo.
COPY . .
RUN npm run build

# ---------- Stage 3: runtime ----------
FROM php:8.4-fpm-alpine AS runtime

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

# Instalador oficial de extensiones: resuelve deps de compilación y limpia solo.
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        gd \
        zip \
        intl \
        opcache \
        redis \
    && apk add --no-cache \
        nginx \
        supervisor \
        curl \
        tzdata \
    && rm -rf /var/cache/apk/*

ENV TZ=America/Santiago
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

WORKDIR /var/www/html

# --- Configuración de servicios ---
COPY docker/prod/nginx.conf        /etc/nginx/nginx.conf
COPY docker/prod/php.ini           /usr/local/etc/php/conf.d/99-mallas-arica.ini
COPY docker/prod/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-mallas-arica.conf
COPY docker/prod/supervisord.conf  /etc/supervisor/supervisord.conf
COPY docker/prod/entrypoint.sh     /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# --- Código de la aplicación ---
# Orden: vendor primero (cambia poco), luego fuente, luego assets compilados.
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Composer del stage `vendor` está compilado contra un PHP más nuevo que el
# runtime (composer:2 exige PHP >= 8.4 en sus versiones recientes) — no es
# portable entre versiones de PHP. Se instala el .phar aquí, contra el PHP
# 8.2 real del runtime, solo para este paso; se elimina al final.
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# composer.json cambió tras el COPY completo: regenerar autoload y correr
# los scripts de paquetes (package:discover) omitidos en el stage vendor.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && rm -f /usr/local/bin/composer \
    && rm -rf /root/.composer

# storage/ y bootstrap/cache/ deben ser escribibles por php-fpm.
RUN mkdir -p storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
