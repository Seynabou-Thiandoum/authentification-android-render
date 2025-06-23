# Image de base avec Apache et PHP 8.2
FROM php:8.2-apache

# Mise à jour des paquets et installation des dépendances pour PostgreSQL
RUN apt-get update && \
    apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Définir le répertoire de travail Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html
WORKDIR /var/www/html

# Copier tous les fichiers de l'application
COPY . .

# Configurer les permissions et activer le module rewrite
RUN chown -R www-data:www-data /var/www/html && \
    a2enmod rewrite

# Exposer le port 80 (utilisé par Apache)
EXPOSE 80

# Commande de démarrage (Apache en foreground)
CMD ["apache2-foreground"]