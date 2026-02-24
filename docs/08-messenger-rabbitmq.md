# MarketFlow — Messenger (async) + RabbitMQ (AMQP) + Failure transport (Doctrine)

Objectif de cette étape :
1) Déclencher des traitements asynchrones avec **Symfony Messenger**.
2) Utiliser **RabbitMQ** comme transport `async` (AMQP).
3) Ajouter un **failure transport** (DLQ applicative) basé sur Doctrine, pour garder les messages en échec et pouvoir les rejouer.

---

## 1) Dépendances / “requires” (Composer + PHP extensions)

### 1.1 Composer
Le projet utilise Symfony Messenger (`symfony/messenger`) avec un transport AMQP.
Le DSN est configuré via `MESSENGER_TRANSPORT_DSN` (voir `.env.local` / `.env.dev`).

> Note : Messenger + AMQP peut fonctionner via un transport basé sur une extension native.

### 1.2 Extension PHP AMQP (RabbitMQ)
Pour parler AMQP efficacement, on installe l’extension PECL `amqp`.

Prérequis système (Alpine) :
- `rabbitmq-c-dev` (lib C)

---

## 2) Docker : ajout de l’extension AMQP

Fichier : `docker/php/Dockerfile`

- installation de `rabbitmq-c-dev`
- `pecl install amqp`
- `docker-php-ext-enable amqp`

Cela permet à PHP-FPM et aux workers CLI d’utiliser AMQP.

---

## 3) Configuration Messenger

Fichier : `config/packages/messenger.yaml`

### 3.1 Transports
- `async` : RabbitMQ (AMQP)
- `failed` : Doctrine (stockage en DB) pour conserver les messages en échec

### 3.2 Routing
Les messages métiers sont routés vers `async`.

Exemple :
- `App\Application\Messaging\Message\OfferTransitioned` → `async`

---

## 4) Messages & Handlers

### 4.1 Message : OfferTransitioned
Message publié à chaque transition du workflow Offer.
Payload minimal :
- `tenantId` (ULID string, identique à `X-Tenant`)
- `offerId` (ULID string)
- `transition`
- `toStatus`

### 4.2 Publisher (Workflow → Messenger)
Subscriber Workflow :
- écoute `workflow.offer_publication.completed`
- dispatch `OfferTransitioned` sur le bus Messenger

### 4.3 Handler
Handler Messenger marqué via attribut :
- `#[AsMessageHandler]`

Il reçoit `OfferTransitioned` et exécute le traitement (placeholder actuellement : log).
Plus tard, il servira à :
- emails async
- indexation Elasticsearch
- notifications
- etc.

---

## 5) Commandes utiles (dev)

### 5.1 Lancer un worker async (RabbitMQ)

- bash docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:consume async -vv --time-limit=3600 --env=dev"


### 5.2 Installer les tables nécessaires aux transports Doctrine
`doctrine:migrations:diff` ne détecte rien ici (car ce n’est pas du mapping ORM).
Il faut utiliser :

- bash docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:setup-transports --env=dev"


Cela crée notamment la table `messenger_messages` (utilisée par le transport `doctrine://`).

### 5.3 Inspecter / rejouer les messages en échec

- bash docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:failed:show --env=dev" docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:failed:retry --env=dev" docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:failed:remove --env=dev"


---

## 6) Notes “standards” (qualité / prod-ready)

### 6.1 Multi-tenant
Règle : un message async doit toujours embarquer le `tenantId`.
Cela évite les fuites cross-tenant dans les workers (qui ne reçoivent pas de header HTTP).

### 6.2 Idempotence
Les handlers doivent être idempotents :
- un message peut être rejoué (retry) ou redélivré
- le traitement ne doit pas produire d’effets de bord en double

### 6.3 Retry + DLQ
- `async` : retry strategy (exponentiel) côté Messenger
- `failed` : stockage des messages définitivement en échec, avec commandes de replay

---

## 7) Dépannage rapide

### “No handler for message …”
Ca arrive si :
- le handler n’est pas autoloadable (nom de fichier ≠ nom de classe PSR-4)
- le cache n’a pas été régénéré

Fix :
- corriger le nom du fichier/classe
- `php bin/console cache:clear --env=dev`
- relancer le worker

### Diff migrations “No changes detected”
Normal : `messenger_messages` n’est pas basé sur tes entités ORM.
Il faut `messenger:setup-transports`.

---

## 8) Prochaine étape prévue
Brancher un vrai cas d’usage :
- **email async sur publish** (Mailer → Mailpit) ou
- **indexation Elasticsearch async** sur publish