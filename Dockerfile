FROM php:8.2-apache

# Installer les dépendances PostgreSQL
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configurer Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html
WORKDIR /var/www/html

COPY . .

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html && \
    a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]