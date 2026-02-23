# 🚀 MarketFlow - Architecture Docker

## 📦 Services

### 🌐 Web (Nginx) - Port 8080
**Rôle :** Serveur web et reverse proxy
- Point d'entrée HTTP de l'application
- Route les requêtes vers PHP-FPM
- Sert les fichiers statiques (CSS, JS, images)
- Configuration dans `docker/nginx/default.conf`

### 🐘 App (PHP-FPM)
**Rôle :** Application backend Symfony/PHP
- Exécute le code métier de l'application
- Traite les requêtes via PHP-FPM
- Communique avec tous les services (DB, Redis, RabbitMQ, etc.)

### 🗄️ Database (PostgreSQL 16) - Port 5432
**Rôle :** Base de données relationnelle principale
- Stockage persistant des données (utilisateurs, commandes, produits, etc.)
- Transactions ACID
- Migrations gérées par Doctrine

### ⚡ Redis - Port 6379
**Rôle :** Cache en mémoire et stockage clé-valeur
- Cache applicatif pour améliorer les performances
- Stockage des sessions utilisateur
- File d'attente légère

### 🐰 RabbitMQ - Ports 5672 (AMQP) / 15672 (Management UI)
**Rôle :** Message broker pour tâches asynchrones
- Gestion des files d'attente de messages
- Traitement asynchrone (emails, notifications, export de données)
- Communication entre services (Event-Driven Architecture)
- **Interface web :** http://localhost:15672 (guest/guest)

### 🔍 Elasticsearch - Port 9200
**Rôle :** Moteur de recherche et analytics
- Recherche full-text avancée
- Indexation des produits et contenus
- Agrégations et statistiques
- Auto-complétion et suggestions

### 📧 Mailpit - Ports 1025 (SMTP) / 8025 (UI)
**Rôle :** Serveur SMTP de développement
- Capture tous les emails envoyés par l'application
- Évite l'envoi de vrais emails en développement
- **Interface web :** http://localhost:8025

---

## 🚀 Démarrage

### Prérequis
- Docker & Docker Compose installés
- Ports libres : 8080, 5432, 6379, 5672, 15672, 9200, 8025, 1025

### Installation

1. **Copier le fichier d'environnement :**
   ```bash
   cp .env.example .env
   ```

2. **Ajuster les variables dans `.env` selon vos besoins**

3. **Démarrer tous les services :**
   ```bash
   docker-compose up -d
   ```

4. **Vérifier l'état des services :**
   ```bash
   docker-compose ps
   ```

5. **Voir les logs :**
   ```bash
   docker-compose logs -f
   ```

---

## 🔧 Commandes utiles

```bash
# Arrêter les services
docker-compose down

# Arrêter et supprimer les volumes (⚠️ perte de données)
docker-compose down -v

# Reconstruire les images
docker-compose build --no-cache

# Exécuter une commande dans le conteneur app
docker-compose exec app bash
docker-compose exec app php bin/console cache:clear

# Accéder à PostgreSQL
docker-compose exec db psql -U marketflow -d marketflow

# Accéder à Redis CLI
docker-compose exec redis redis-cli

# Voir les logs d'un service spécifique
docker-compose logs -f app
```

---

## 🌐 URLs des interfaces

- **Application :** http://localhost:8080
- **RabbitMQ Management :** http://localhost:15672 (guest/guest)
- **Mailpit (emails) :** http://localhost:8025
- **Elasticsearch :** http://localhost:9200

---

## 📊 Flux de données

```
Utilisateur
    ↓
[Nginx:8080] → [PHP-FPM] → [PostgreSQL] (données)
                    ↓
                    ├→ [Redis] (cache/sessions)
                    ├→ [RabbitMQ] (tâches async)
                    ├→ [Elasticsearch] (recherche)
                    └→ [Mailpit] (emails dev)
```

---

## 🔒 Sécurité

⚠️ **Important :** Les valeurs par défaut sont pour le développement uniquement !

En production, pensez à :
- Changer tous les mots de passe
- Activer la sécurité Elasticsearch
- Utiliser un vrai serveur SMTP
- Configurer les pare-feu
- Utiliser des secrets Docker/Kubernetes

---

## 📝 Variables d'environnement

Toutes les variables sont documentées dans `.env.example`.
Les valeurs par défaut sont définies dans `docker-compose.yml` avec la syntaxe `${VAR:-default}`.

---

## 🆘 Dépannage

### Les conteneurs ne démarrent pas
```bash
docker-compose logs
docker-compose ps
```

### Ports déjà utilisés
Modifiez les ports dans `.env`

### Problèmes de permissions
```bash
sudo chown -R $USER:$USER ./backend
```

### Réinitialiser complètement
```bash
docker-compose down -v
docker system prune -a
docker-compose up -d --build
```

