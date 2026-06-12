# ============================================================
# Dockerfile — Latin Shop
# PHP 8.3 + Apache | Producción lista
# ============================================================
FROM php:8.3-apache

LABEL maintainer="Latin Shop <dev@latln-shop.com>"
LABEL description="Latin Shop — PHP 8.3 + Apache"

# ─── Variables de build ──────────────────────────────────────
ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

# ─── Dependencias del sistema ────────────────────────────────
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

# ─── Extensiones PHP ─────────────────────────────────────────
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

# ─── Redis (phpredis) ────────────────────────────────────────
RUN pecl install redis \
    && docker-php-ext-enable redis

# ─── Composer ────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ─── Apache: habilitar mod_rewrite ───────────────────────────
RUN a2enmod rewrite headers deflate expires

# ─── Configuración PHP producción ────────────────────────────
COPY docker/php/php.ini         /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini     /usr/local/etc/php/conf.d/opcache.ini

# ─── Configuración Apache ────────────────────────────────────
COPY docker/nginx/000-default.conf /etc/apache2/sites-available/000-default.conf

# ─── Directorio de trabajo ───────────────────────────────────
WORKDIR /var/www/html

# ─── Copiar código fuente ────────────────────────────────────
COPY . .

# ─── Instalar dependencias PHP ───────────────────────────────
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && composer dump-autoload --optimize

# ─── Crear directorios necesarios fuera del public ───────────
RUN mkdir -p /var/www/storage/uploads \
             /var/www/storage/logs \
             /var/www/storage/cache \
             /var/www/storage/backups \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 750 /var/www/storage

# ─── Permisos de la app ──────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# ─── Script de inicio ────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
