FROM php:8.4-fpm

# System deps for common PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions (mysql + common for Laravel)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app
