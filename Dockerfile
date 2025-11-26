# Usar PHP 8.2 con Apache
FROM php:8.2-apache

# -----------------------------
# Instalar dependencias del sistema
# -----------------------------
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# -----------------------------
# Instalar extensiones PHP
# -----------------------------
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# -----------------------------
# Instalar Composer
# -----------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------
# Configurar directorio de trabajo
# -----------------------------
WORKDIR /var/www/html

# -----------------------------
# Copiar composer.json y composer.lock primero
# -----------------------------
COPY composer.json composer.lock* ./

# -----------------------------
# Instalar dependencias de Composer
# -----------------------------
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction

# -----------------------------
# Copiar el resto de archivos del proyecto
# -----------------------------
COPY . .

# -----------------------------
# Limpiar caches de Laravel
# -----------------------------
RUN rm -rf bootstrap/cache/*.php \
    && rm -rf storage/framework/cache/data/* \
    && rm -rf storage/framework/views/*

# -----------------------------
# Configurar permisos
# -----------------------------
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# -----------------------------
# Configurar Apache para Railway
# -----------------------------
# Cambiar puerto a 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/80/8080/g' /etc/apache2/ports.conf

# Activar mod_rewrite
RUN a2enmod rewrite

# Apuntar DocumentRoot al directorio public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# -----------------------------
# Script de inicio que limpia caches y ejecuta migraciones
# -----------------------------
RUN echo '#!/bin/bash\n\
php artisan config:clear\n\
php artisan cache:clear\n\
php artisan migrate --force\n\
apache2-foreground' > /start.sh \
    && chmod +x /start.sh

# -----------------------------
# Exponer puerto 8080
# -----------------------------
EXPOSE 8080

# -----------------------------
# Comando de inicio
# -----------------------------
CMD ["/start.sh"]

