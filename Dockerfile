# Stage 1: PHP-FPM com dependências Laravel
FROM php:8.2-fpm AS php

# Instalar libs do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    zlib1g-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    nginx \
    supervisor \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        mbstring \
        zip \
        pdo_mysql \
        bcmath \
        intl \
        gd \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Criar diretório da aplicação
WORKDIR /var/www

# Copiar apenas composer.json e composer.lock para instalar dependências primeiro (cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
RUN php artisan key:generate
RUN php artisan migrate --force

# Copiar o restante do projeto
COPY . .

# Ajustar permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

    

# Copiar configuração do Nginx
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

# Configuração do Supervisor para rodar Nginx + PHP-FPM juntos
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expor porta HTTP
EXPOSE 80

# Rodar supervisor (que gerencia Nginx + PHP-FPM)
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
