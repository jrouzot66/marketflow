# MarketFlow — Mailer async (Mailpit + Messenger)

Objectif de cette étape :
1) Envoyer des **emails asynchrones** via Messenger et RabbitMQ.
2) Utiliser **Mailpit** comme serveur SMTP local (dev/test).
3) Intégrer l'email dans le workflow : **quand une Offer passe en "published"** → email de notification.

---

## 1) Configuration Mailer (Symfony)

Fichier : `config/packages/mailer.yaml`

Par défaut, Symfony Mailer est configuré avec un DSN via la variable `MAILER_DSN` (`.env`).

### 1.1 DSN pour Mailpit (dev)
MAILER_DSN="smtp://mailpit:1025"


Breakdown :
- `smtp://` : protocole SMTP
- `mailpit` : hostname du service Docker (`docker-compose.yml`)
- `1025` : port SMTP de Mailpit

### 1.2 Adresses sender/reply (optionnel)
On peut ajouter dans `config/packages/mailer.yaml` :

yaml framework: mailer: from: 'noreply@marketflow.local'


Ou les définir directement dans le code (Email builder).

---

## 2) Message & Handler

### 2.1 Message : SendOfferPublishedNotification
Classe : `App\Application\Messaging\Message\SendOfferPublishedNotification`

Payload minimal :
- `tenantId` (ULID string)
- `offerId` (ULID string)
- `offerTitle` (string)

Rôle : transporter les infos nécessaires au handler pour composer l'email.

### 2.2 Handler : SendOfferPublishedNotificationHandler
Classe : `App\Infrastructure\Messenger\Handler\SendOfferPublishedNotificationHandler`

Responsabilités :
- reçoit le message async (quand RabbitMQ le délivre)
- utilise `MailerInterface` pour construire et envoyer un `Email`
- log en cas de succès / erreur
- rejette l'exception en cas d'échec (pour que Messenger la rejoue)

**Important (multi-tenant)** :
- le message embarque `tenantId` → aucune fuite cross-tenant
- l'email ne contient que les infos du message (pas de requête DB).

---

## 3) Intégration : Workflow → Messenger

Fichier : `App\Interface\Workflow\WorkflowMessengerPublisherSubscriber`

Logique :
- quand le workflow termine une transition sur une Offer,
- on dispatch `OfferTransitioned` (audit general),
- **et si** `toStatus === 'published'`, on dispatch aussi `SendOfferPublishedNotification`.

> Design : ce subscriber "branche" le workflow métier aux événements asynchrones.
> Plus tard, on peut ajouter d'autres messages (indexation ES, webhook, etc.) de la même façon.

---

## 4) Routing Messenger

Fichier : `config/packages/messenger.yaml`

yaml routing: 'App\Application\Messaging\Message\SendOfferPublishedNotification': async


Le message est routé vers le transport `async` (RabbitMQ).

---

## 5) Prérequis & dépendances

### 5.1 Symfony Mailer
Déjà inclus via le bundle Symfony standard (FrameworkBundle).

### 5.2 Service Docker : Mailpit
Configuré dans `docker-compose.yml` :
- Port SMTP : `1025`
- UI web : `8025`

### 5.3 Services connexes
- RabbitMQ (transport async)
- Messenger worker (consomme les messages)

---

## 6) Flux complet (user perspective)

1. **Créer une offre** → POST `/api/offers` → status `draft`
2. **Submit** → POST `/api/offers/{id}/submit` → status `review`
3. **Approve** → POST `/api/offers/{id}/approve` → status `approved`
4. **Publish** → POST `/api/offers/{id}/publish` → status `published`
    - Workflow → `OfferTransitioned` (async)
    - Workflow → `SendOfferPublishedNotification` (async)
5. **Worker consomme les messages** :
    - `OfferTransitionedHandler` → log
    - `SendOfferPublishedNotificationHandler` → email via Mailpit
6. **Vérifier l'email** → http://localhost:8025

---

## 7) Commandes de debug

### 7.1 Voir les messages en attente

bash docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:failed:show --env=dev"


### 7.2 Rejouer les emails en échec

bash docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:failed:retry --env=dev"


### 7.3 Lancer/relancer le worker

bash docker compose exec app sh -lc "cd /var/www/html && php bin/console messenger:consume async -vv --time-limit=3600 --env=dev"


### 7.4 Vérifier la config Mailer

bash docker compose exec app sh -lc "cd /var/www/html && php bin/console debug:config framework mailer --env=dev"


---

## 8) Architecture (multi-tenant & idempotence)

### 8.1 Multi-tenant
- Message embarque `tenantId` ✅
- Handler ne peut pas faire de "fuite" (pas de query globale) ✅
- Email adressé selon les infos du message (pas de lookup DB) ✅

### 8.2 Idempotence
- Handler est idempotent : rejouer le message = renvoyer le même email.
- Mailpit ne déduplique pas (normal en dev).
- En prod, on utiliserait un template + tracking d'email pour éviter les doubles.

### 8.3 Retry & failure
- Retry exponential (1s → 2s → 4s → 8s → 16s)
- Au-delà de 5 retries → failure transport (Doctrine)
- Commandes pour inspecter / rejouer les failures.

---

## 9) Améliorations futures

- **Template Twig** : plutôt que du HTML hardcodé, charger depuis `templates/emails/`
- **Destinataires dynamiques** : lire depuis config ou DB (user qui a créé l'offre, admin, etc.)
- **Préférences d'email** : permettre à l'utilisateur de se désabonner de certains types
- **Tracking** : pixel unique par email, webhook de bounces
- **A/B testing** : variantes de sujet / contenu
- **Rate limiting** : ne pas spammer un utilisateur

---

## 10) Fichiers concernés

- `config/packages/mailer.yaml` (si config avancée)
- `.env.dev` → `MAILER_DSN="smtp://mailpit:1025"`
- `src/Application/Messaging/Message/SendOfferPublishedNotification.php`
- `src/Infrastructure/Messenger/Handler/SendOfferPublishedNotificationHandler.php`
- `src/Interface/Workflow/WorkflowMessengerPublisherSubscriber.php`
- `config/packages/messenger.yaml` (routing)
- `docker-compose.yml` (service Mailpit)

---

## 11) Vérification rapide (checklist)

- [ ] `MAILER_DSN` pointe sur Mailpit (1025)
- [ ] Message + Handler créés et routés
- [ ] Workflow dispatcher le message si `published`
- [ ] Worker en cours d'exécution
- [ ] Mailpit accessible sur http://localhost:8025
- [ ] Après `publish`, un email apparaît dans Mailpit
- [ ] Logs du worker montrent "handled successfully"