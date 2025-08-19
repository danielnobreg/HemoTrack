FROM php:8.2-fpm

# Instalar dependências do sistema e libs para extensões PHP
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

# Copiar Composer da imagem oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Criar diretório da aplicação
WORKDIR /var/www

# Copiar apenas composer.json e composer.lock para instalar dependências primeiro (cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress --prefer-dist

# Copiar todo o projeto
COPY . .

# Ajustar permissões para o storage e bootstrap/cache (Laravel precisa disso)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Rodar composer novamente (executando scripts pós-autoload)
RUN composer install --no-dev --optimize-autoloader

# Expõe a porta do PHP-FPM
EXPOSE 9000

# Inicia o PHP-FPM
CMD ["php-fpm"]