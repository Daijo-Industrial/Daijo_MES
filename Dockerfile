# ==========================================
# Stage 1: Build Frontend Assets (Vite)
# ==========================================
FROM node:20-bookworm-slim AS frontend
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY public/ ./public/

RUN npm run build

# ==========================================
# Stage 2: Install Composer Dependencies
# ==========================================
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ==========================================
# Stage 3: Production PHP-FPM Image
# ==========================================
FROM php:8.3-fpm-bookworm AS production

# Install System Dependencies & MySQL client for mysqldump backup
RUN apt-get update && apt-get install -y --no-install-recommends \
    default-mysql-client \
    curl \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        intl \
        exif \
        pcntl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Custom PHP & OPcache configuration
COPY docker/production/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/production/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy Project Files
COPY . .

# Copy Vendor from Stage 2
COPY --from=vendor /app/vendor/ ./vendor/

# Copy Compiled Assets from Stage 1
COPY --from=frontend /app/public/build/ ./public/build/

# Copy and Set Permissions for Entrypoint
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Re-run composer package discovery / dump-autoload for production
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Ensure proper ownership of Laravel writable directories
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
