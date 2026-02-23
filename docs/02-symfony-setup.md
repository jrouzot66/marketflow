# Mise à jour vers Symfony 8 et PHP 8.4

Ce document décrit les étapes nécessaires pour mettre à jour notre environnement de développement afin d'utiliser Symfony 8, qui requiert PHP 8.4.

## 1. Mise à jour de l'environnement Docker

Symfony 8 nécessitant PHP 8.4, nous devons reconstruire notre image Docker pour le service `app`.

### a. Destruction de l'ancienne image

Commencez par supprimer l'image Docker existante pour vous assurer que les nouvelles modifications seront bien prises en compte.

```bash
docker compose down --rmi local
```

### b. Adaptation du Dockerfile

Modifiez le `Dockerfile` de votre service `app` pour utiliser une image de base PHP 8.4.

Exemple de modification dans le `Dockerfile` :
```dockerfile
# Remplacez l'ancienne version de PHP par la nouvelle
FROM php:8.4-fpm-alpine
```

### c. Configuration des permissions utilisateur

Il est crucial que l'utilisateur dans le conteneur corresponde à votre utilisateur local pour éviter les problèmes de permission de fichiers.

1.  Récupérez votre ID utilisateur (UID) et ID de groupe (GID) locaux :
    ```bash
    id -u
    id -g
    ```
2.  Assurez-vous que les commandes `addgroup` et `adduser` dans votre `Dockerfile` utilisent ces IDs.

    ```dockerfile
    # Adaptez les valeurs 1000 avec votre UID et GID
    RUN addgroup -g 1000 -S www-data && \
        adduser -u 1000 -S www-data -G www-data
    ```

### d. Relance et vérification

Une fois le `Dockerfile` mis à jour, relancez vos services.

```bash
docker compose up -d --build
```

Vérifiez que la version de PHP et l'utilisateur dans le conteneur sont corrects :

```bash
docker compose exec app sh -lc "id && php -v"
```

## 2. Installation de Symfony 8

Maintenant que l'environnement est prêt, nous pouvons installer Symfony et ses dépendances.

### a. Création du projet

Exécutez la commande suivante pour créer un nouveau projet Symfony 8 dans le volume monté.

```bash
docker compose exec app sh -lc "cd /var/www/html && composer create-project symfony/skeleton:^8.0 ."
```

### b. Installation des dépendances

Installez les paquets Symfony nécessaires pour le projet.

```bash
# Twig
docker compose exec app sh -lc "cd /var/www/html && composer require symfony/twig-bundle"

# Autres paquets essentiels
docker compose exec app sh -lc "cd /var/www/html && composer require \
  symfony/orm-pack \
  symfony/messenger \
  symfony/workflow \
  symfony/mailer \
  symfony/redis-messenger \
  symfony/http-client \
  symfony/serializer-pack \
  symfony/validator \
  symfony/uid \
  symfony/asset-mapper \
  symfony/stimulus-bundle \
  symfony/ux-turbo"
```

## 3. Configuration de l'application

### a. Création de la base de données

Lancez la commande Doctrine pour créer la base de données si elle n'existe pas.

```bash
docker compose exec app sh -lc "cd /var/www/html && php bin/console doctrine:database:create --if-not-exists"
```

### b. Configuration du fichier `.env`

Adaptez le fichier `.env` de votre application Symfony. Profitez-en pour générer une clé secrète sécurisée.

1.  Générez un `APP_SECRET` :
    ```bash
    openssl rand -hex 32
    ```
2.  Copiez la valeur générée et mettez à jour votre fichier `.env` avec les bonnes variables de connexion à la base de données, Messenger, etc., en vous basant sur votre configuration Docker.

