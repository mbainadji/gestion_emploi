FROM php:8.2-apache

# Installation des extensions nécessaires pour MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Activation de mod_rewrite pour Apache
RUN a2enmod rewrite

# Copie des fichiers du projet dans le serveur
COPY . /var/www/html/

# Ajustement des permissions
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
