# ============================================================
# Stage 1: base — system dependencies, PHP extensions, Composer
# ============================================================
FROM php:8.3-fpm-alpine AS base

# Install system dependencies yang dibutuhkan Laravel
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    icu-dev

# Compile dan install PHP extensions yang dibutuhkan Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension untuk caching dan sessions
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Copy Composer dari official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

# ============================================================
# Stage 2: development — bind-mounted code, entrypoint installs deps
# ============================================================
FROM base AS development

COPY docker/php/entrypoint.dev.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ============================================================
# Stage 3: production — baked-in code, optimised for performance
# ============================================================
FROM base AS production

# Copy seluruh aplikasi ke dalam container
COPY . .

# Install production dependencies (no dev packages)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Buat direktori yang dibutuhkan Laravel
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache

# Set proper ownership dan permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage -type f -exec chmod 664 {} \; \
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;

COPY docker/php/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
