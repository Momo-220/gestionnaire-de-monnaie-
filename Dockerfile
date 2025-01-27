FROM php:8.1-apache

# Installation des dépendances nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Activation des extensions PHP nécessaires
RUN docker-php-ext-install json

# Activation du module rewrite d'Apache
RUN a2enmod rewrite

# Configuration d'Apache
COPY .htaccess /var/www/html/

# Copie des fichiers de l'application
COPY . /var/www/html/

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposition du port 80
EXPOSE 80 