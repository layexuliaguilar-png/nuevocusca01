# Usar la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalar dependencias del sistema y el driver de PostgreSQL para PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copiar todo el código de la aplicación al directorio raíz de Apache
COPY . /var/www/html/

# Habilitar el módulo de reescritura de Apache (útil para rutas amigables)
RUN a2enmod rewrite

# Exponer el puerto 80 (Render lo mapea automáticamente)
EXPOSE 80
