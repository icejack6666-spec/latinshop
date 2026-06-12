FROM php:8.3-apache

LABEL maintainer="Latin Shop <dev@latln-shop.com>"
LABEL description="Latin Shop — PHP 8.3 + Apache"

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
        bcmath \
        xml \
        curl

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite headers deflate expires

COPY docker/php/php.ini         /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini     /usr/local/etc/php/conf.d/opcache.ini

COPY docker/nginx/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction -vvv

RUN mkdir -p /var/www/storage/uploads \
             /var/www/storage/logs \
             /var/www/storage/cache \
             /var/www/storage/backups \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 750 /var/www/storage
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
