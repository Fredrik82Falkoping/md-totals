# --- PHP VERSION ---
# Byt mellan 8.4 eller 8.5 här:
FROM php:8.4-fpm

# --- System dependencies ---
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libonig-dev libxml2-dev libsqlite3-dev \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

# --- PHP extensions för Laravel ---
RUN docker-php-ext-install \
    pdo pdo_mysql pdo_sqlite \
    mbstring tokenizer xml gd zip

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- App directory ---
WORKDIR /var/www/html
COPY . .

# --- Installera dependencies ---
RUN composer install --no-dev --optimize-autoloader

# --- Cachea Laravel config ---
RUN php artisan config:cache || true

# --- Exponera port ---
EXPOSE 8000

# --- Starta Laravel ---
CMD php artisan serve --host=0.0.0.0 --port=8000
