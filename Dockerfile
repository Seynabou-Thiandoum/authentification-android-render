# Utiliser une image officielle PHP
FROM php:8.1-cli

# Copier tous les fichiers dans le conteneur
COPY . /usr/src/app
WORKDIR /usr/src/app

# Exposer le port utilisé par le serveur PHP
EXPOSE 10000

# Commande pour démarrer ton projet PHP
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
