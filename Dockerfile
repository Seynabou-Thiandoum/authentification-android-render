# Image de base avec Apache pour le déploiement web
FROM php:8.2-apache

# Installer les dépendances nécessaires pour PostgreSQL
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql

# Configurer Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html && \
    a2enmod rewrite

# Port exposé (doit correspondre à celui utilisé dans votre code PHP)
EXPOSE 80

# Démarrer Apache au lancement
CMD ["apache2-foreground"]