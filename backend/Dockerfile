FROM php:8.4-cli

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl zip pdo_pgsql sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Upload limit matches StoreImportRequest::MAX_SIZE_KB (10 MB) plus multipart overhead.
RUN printf 'upload_max_filesize=10M\npost_max_size=12M\n' > /usr/local/etc/php/conf.d/uploads.ini

RUN groupadd -g ${GID} app && useradd -u ${UID} -g app -m app

WORKDIR /var/www/html

COPY --chown=app:app composer.json composer.lock ./
USER app
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY --chown=app:app . .
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
