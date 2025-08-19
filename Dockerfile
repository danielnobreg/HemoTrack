FROM php:8.2-fpm

#Install minimal dependencies (skip DB drivers)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    zip \
    zlib1g-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install mbstring zip \
    && rm -rf /var/lib/apt/lists/*

#Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY app files
COPY . .

#Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]
