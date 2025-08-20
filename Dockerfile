# Usa PHP com Apache
FROM php:8.2-apache

# Define diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && a2enmod rewrite

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do Laravel
COPY . .

# Instala dependências do Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Dá permissão para storage e bootstrap
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Define variável de ambiente (Laravel roda em prod)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV APP_ENV=production
ENV APP_DEBUG=false

# Ajusta o Apache para rodar do public/
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Porta usada pelo Render
EXPOSE 8080

# Render precisa do Apache rodando na porta 8080
CMD ["apache2-foreground"]
