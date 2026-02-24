# MarketFlow — Étape DDD “Offer” (agrégat Domain) + adapter Doctrine + API

Objectif de cette étape :
1) Introduire un **modèle métier (Domain)** pour Offer (VO + invariants + events).
2) Garder Doctrine comme **détail technique** via un adapter (Infrastructure).
3) Conserver une API REST simple, pilotée par des **use-cases** (Application) et des **DTO**.

---

## 1) Pourquoi on fait ça (diff avec un Symfony “classique”)

### Symfony traditionnel (souvent)
- Controller → appelle Doctrine directement
- Entity Doctrine → finit par porter des règles métier
- Logique dispersée (controllers/services/repositories/entities)

### DDD (pragmatique) ici
- **Domain** : règles métier pures, invariants, événements (pas de Symfony, pas de Doctrine).
- **Application** : cas d’usage (CreateOffer/ListOffers), orchestration.
- **Infrastructure** : Doctrine/RabbitMQ/ES/Mailer (détails d’implémentation).
- **Interface (HTTP)** : lecture/validation input + mapping + réponse JSON.

Bénéfice : quand on ajoutera Workflow, async, search, audit… on s’accroche au **Domain** proprement.

---

## 2) Domain : Offer en agrégat + VO

### 2.1 Value Objects
- `OfferId` (ULID)
- `TenantId` (ULID côté Domain — cohérent pour la logique métier)
- `OfferTitle` (trim, non vide, max 255)

### 2.2 Agrégat Offer
- `Offer::create(tenantId, title)` : crée une offre valide
- `Offer::rehydrate(...)` : reconstruit une Offer depuis la persistance (sans rejouer d’event)

### 2.3 Domain events
- `OfferCreated` : événement émis lors de la création (utile plus tard pour indexation ES, mail, audit, etc.)
- `pullEvents()` : permet à l’infrastructure de récupérer les événements à publier (plus tard).

---

## 3) Application : use-cases + DTO

### 3.1 DTO de sortie
- `OfferView` : représentation simple (id, tenantId, title) renvoyée à l’API.

### 3.2 Ports (interfaces)
- `OfferRepository` : interface côté Application, qui manipule des objets **Domain**.

### 3.3 Use-cases
- `CreateOffer` :
    - lit le tenant courant via `TenantContext`
    - crée une Offer côté Domain
    - persiste via `OfferRepository`
    - renvoie `OfferView`

- `ListOffers` :
    - lit le tenant courant
    - liste via `OfferRepository`
    - renvoie une liste de `OfferView`

---

## 4) Infrastructure : adapter Doctrine (Entity <-> Domain)

### 4.1 Entité Doctrine `App\Entity\Offer`
- immuable (constructeur obligatoire)
- stocke :
    - `id` en ULID (Doctrine type `ulid`)
    - `tenant_id` en string UUID RFC4122 (36 chars)
    - `title` string

### 4.2 Mapping tenant : point important
- À l’entrée HTTP, le tenant est un **ULID** (`X-Tenant`).
- Pour le filtrage DB, on utilise **RFC4122** (`TenantId->toRfc4122()`).
- Donc la DB filtre avec un UUID string.
- Conséquence : **on ne peut pas “reconstruire” un ULID depuis l’UUID** stocké.
    - On conserve donc côté Domain/API la valeur ULID du tenant courant (contexte).

---

## 5) API : contrôleur = transport only
Le contrôleur :
- parse le JSON
- valide un DTO HTTP (`CreateOfferRequest`)
- appelle un use-case (`CreateOffer`, `ListOffers`)
- renvoie du JSON basé sur `OfferView`

---

## 6) Prochaine étape prévue
1) Tests automatisés (PHPUnit) :
    - header `X-Tenant` obligatoire
    - isolement cross-tenant sur `/api/offers`
2) Puis Workflow + audit (draft → review → approved → published) en s’appuyant sur le Domain.