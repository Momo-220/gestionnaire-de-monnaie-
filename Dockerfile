FROM php:8.1-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Activation des modules Apache nécessaires
RUN a2enmod rewrite headers expires deflate

# Configuration d'Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copie des fichiers de l'application
COPY . /var/www/html/

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposition du port 80
EXPOSE 80 