# MarketFlow — Workflow Offer (publication) + Audit trail

Objectif de cette étape :
1) Mettre en place un **workflow de publication** sur les Offers (draft → review → approved → published…).
2) Exposer des **endpoints REST orientés actions** (submit/approve/publish/reject/archive).
3) Ajouter un **audit trail** automatique (qui enregistre chaque transition).

---

## 1) Pourquoi un Workflow ?

La publication d’une offre n’est pas un simple champ “status” modifiable librement.
On veut :
- imposer des **transitions autorisées**,
- empêcher les sauts illégaux (ex : draft → published),
- centraliser la logique de cycle de vie,
- faciliter l’intégration d’effets de bord plus tard (emails, indexation ES, notifications).

Symfony Workflow fournit :
- un graphe d’états (places),
- des transitions nommées,
- des guards (plus tard),
- des événements (parfait pour l’audit et le async).

---

## 2) Modèle : état stocké sur `offers.status`

### 2.1 Champ `status` (DB + Doctrine)
- `offers.status` est une colonne string (ex : `draft`, `review`, `approved`, `published`).
- L’entité `App\Entity\Offer` expose :
    - un getter Symfony-compatible : `getStatus(): string`
    - un setter : `setStatus(string $status): void`

> Pourquoi `getStatus()` est important :
> Le marquage du workflow en mode `method` attend un getter “classique”.
> Une méthode custom `status()` n’est pas reconnue comme getter par Symfony Workflow.

---

## 3) Configuration du Workflow

Fichier : `config/packages/workflow.yaml`

- type : `state_machine`
- supports : `App\Entity\Offer`
- marking_store : `method`, `property: status`
- initial : `draft`

### 3.1 Places
- `draft`
- `review`
- `approved`
- `published`
- `rejected`
- `archived`

### 3.2 Transitions
- `submit` : draft → review
- `approve` : review → approved
- `publish` : approved → published
- `reject` : review → rejected
- `archive` : (draft|review|approved|published|rejected) → archived

---

## 4) Use-case applicatif : appliquer une transition

Classe : `App\Application\Offer\ApplyOfferTransition`

Responsabilités :
- valider que le tenant est présent (`TenantContext`)
- charger l’offre par ID (Doctrine)
- vérifier que la transition est autorisée (`workflow->can(...)`)
- appliquer la transition (`workflow->apply(...)`)
- flush Doctrine

> Multi-tenant :
> Le filtre Doctrine tenant est déjà activé sur `/api/*`.
> Donc une Offer d’un autre tenant est invisible (et devient “not found”).

---

## 5) Endpoints REST (actions)

Contrôleur : `App\Controller\Api\OfferWorkflowController`

Routes (POST) :
- `/api/offers/{id}/submit`
- `/api/offers/{id}/approve`
- `/api/offers/{id}/publish`
- `/api/offers/{id}/reject`
- `/api/offers/{id}/archive`

Chaque endpoint :
- exige `X-Tenant: <ULID>`
- appelle `ApplyOfferTransition`
- renvoie au minimum :
    - `id`
    - `status`

---

## 6) Audit trail des transitions

### 6.1 Table `workflow_audit`
Entity : `App\Entity\WorkflowAudit`

Champs principaux :
- `tenant_id` : UUID RFC4122 string (36)
- `subject_type` : ex `offer`
- `subject_id` : id offer (ULID string)
- `transition` : nom de transition (submit/approve/…)
- `from_place` : état avant (nullable)
- `to_place` : état après
- `occurred_at` : date/heure
- `actor` : nullable (sera branché à l’utilisateur quand JWT sera en place)

### 6.2 Subscriber
Subscriber : `App\Interface\Workflow\WorkflowAuditSubscriber`

Événement écouté :
- `workflow.offer_publication.completed`

Rôle :
- lorsque le workflow termine une transition sur une Offer,
    - persist un `WorkflowAudit`
    - flush

---

## 7) Binding DI du workflow
Fichier : `config/services.yaml`

On bind l’instance du state machine sur l’argument attendu :

- `Symfony\Component\Workflow\WorkflowInterface $offerPublication: '@state_machine.offer_publication'`

Cela évite d’injecter des service IDs “à la main” partout.

---

## 8) Vérification manuelle (curl)

### 8.1 Création d’offre

- bash curl -i -X POST "[http://localhost:8080/api/offers](http://localhost:8080/api/offers)"
  -H "X-Tenant: <TENANT_ULID>"
  -H "Content-Type: application/json"
  --data '{"title":"Mon offre"}'


### 8.2 Transition submit
- bash curl -i -X POST "[http://localhost:8080/api/offers/](http://localhost:8080/api/offers/)<OFFER_ID>/submit"
  -H "X-Tenant: <TENANT_ULID>"


### 8.3 Vérifier l’audit dans Postgres

bash docker compose exec db sh -lc "psql -U marketflow -d marketflow -c
"select transition, from_place, to_place, occurred_at from workflow_audit order by id desc limit 10;""


---

## 9) Prochaines améliorations prévues
- Guards / permissions (ex : seul un rôle peut `approve`)
- Actor réel (JWT user → audit.actor)
- Endpoint `GET /api/offers/{id}` avec :
    - état
    - transitions disponibles (`getEnabledTransitions`)
- Émission d’événements métier / messages Messenger lors des transitions :
    - email de notification
    - indexation Elasticsearch
    - audit asynchrone si besoin