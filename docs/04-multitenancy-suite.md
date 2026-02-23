# MarketFlow — Doctrine multi-tenant + première verticale Offers (DTO + REST)

Ce document fait suite à : `docs/03-multitenancy-socle.md`.

Objectif de cette étape :
1. rendre la persistance **tenant-aware** (isolation automatique des données par tenant),
2. prouver l’isolation avec une première verticale **Offer**,
3. rester aligné avec une approche **DDD sous Symfony 8** (même si on démarre pragmatiquement avec une entité Doctrine).

---

## 1) Ce qui a été ajouté / modifié

### 1.1. Marquage des entités “tenant-owned”
Nous avons introduit une interface marqueur :

- `App\Domain\Tenant\TenantOwned`

**Rôle :**
- exprimer qu’une donnée appartient à un tenant,
- permettre un filtrage automatique côté Doctrine sans “copier-coller” `WHERE tenant_id = ...` partout.

✅ DDD : le concept “appartient à un tenant” est un **concept de domaine** (un invariant transverse), donc il a sa place côté `Domain`.

---

## 2) Filtrage automatique Doctrine : `TenantFilter`

### 2.1. Filtre SQL Doctrine
Nous avons ajouté :

- `App\Infrastructure\Doctrine\Filter\TenantFilter` (hérite de `Doctrine\ORM\Query\Filter\SQLFilter`)

**Rôle :**
- pour toute entité qui implémente `TenantOwned`, Doctrine ajoute automatiquement une contrainte SQL :
    - `... AND <alias>.tenant_id = :tenant_id`

✅ Symfony/Doctrine :
- ce filtre est activé au niveau ORM, ce qui réduit le risque d’oublier le `tenant_id` dans une requête.

✅ DDD / Clean Architecture :
- le filtre est dans `Infrastructure` (c’est une mécanique de persistance),
- `Domain` ne dépend pas de Doctrine.

---

## 3) Activation du filtre dans Symfony

Le filtre est déclaré dans :

- `config/packages/doctrine.yaml`

Sous :

- `doctrine.orm.filters.tenant.class = App\Infrastructure\Doctrine\Filter\TenantFilter`

✅ Symfony 8 : configuration standard et lisible, sans magie additionnelle.

---

## 4) Brancher la résolution de tenant (HTTP) au filtre Doctrine

### 4.1. Subscriber HTTP dédié au filtre
Nous avons ajouté :

- `App\Interface\Http\Tenant\TenantDoctrineFilterSubscriber`

**Rôle :**
- s’exécute sur `kernel.request`,
- uniquement sur les routes `/api/*`,
- lit le tenant courant via `TenantContext`,
- active le filtre Doctrine `tenant`,
- définit le paramètre `tenant_id`.

### 4.2. Ordre d’exécution (important)
- `TenantRequestSubscriber` résout `X-Tenant` et renseigne `TenantContext` (priorité haute).
- `TenantDoctrineFilterSubscriber` tourne ensuite (priorité plus basse), car il a besoin du `TenantContext`.

✅ Symfony 8 : usage correct de `EventSubscriberInterface`, `isMainRequest()`, et des priorités.

---

## 5) Choix de stockage en base : `tenant_id` en UUID Postgres

### 5.1. Pourquoi pas ULID natif en base ?
PostgreSQL ne dispose pas d’un type ULID natif. Deux options :
- stocker en texte (simple mais moins performant/strict),
- ou convertir en UUID (type natif Postgres).

### 5.2. Choix retenu
Nous stockons `tenant_id` en **UUID** (type Postgres `uuid`).

Conversion :
- le tenant est fourni en ULID (`X-Tenant`),
- il est converti en UUID RFC4122 via :
    - `TenantId::toRfc4122()`

✅ Avantages :
- type strict en base,
- indexation efficace,
- cohérence avec des stratégies futures (filtres, jointures, perfs).

---

## 6) Première verticale : Offer (Entity + REST minimal)

### 6.1. Entité Doctrine `Offer`
Nous avons ajouté :

- `App\Entity\Offer`

Caractéristiques :
- `id` : `ulid` (Doctrine type `ulid`)
- `tenant_id` : `uuid` (Doctrine type `uuid`)
- index DB sur `tenant_id` (pour accélérer les filtrages tenant)

L’entité implémente :
- `App\Domain\Tenant\TenantOwned`

✅ Important :
- le simple fait d’implémenter `TenantOwned` suffit à rendre l’entité filtrée par tenant via `TenantFilter`.

### 6.2. DTO input (validation)
Nous avons ajouté un DTO HTTP :

- `App\Interface\Http\Api\Dto\CreateOfferRequest`

Rôle :
- capturer l’input (`title`)
- porter les règles de validation Symfony Validator

✅ Symfony 8 : usage des attributs `#[Assert\...]` sur DTO.
✅ DDD : séparation claire “input HTTP” vs “modèle de persistance” (même si on n’a pas encore le modèle Domain pur).

---

## 7) Endpoints REST : création et listing

Nous avons ajouté :

- `POST /api/offers` : crée une offre pour le tenant courant
- `GET /api/offers` : liste les offres du tenant courant

### 7.1. Pourquoi la liste ne filtre pas explicitement `tenant_id` ?
Le code du contrôleur ne met aucun `WHERE tenant_id = ...`.

C’est volontaire :
- le filtrage tenant est **automatique** (Doctrine filter),
- on limite le risque humain d’oublier une contrainte,
- la lecture “par défaut” devient sûre.

⚠️ Note :
Le filtre Doctrine est un excellent garde-fou, mais on ajoutera ensuite :
- des garde-fous applicatifs au niveau Repository / Use-cases,
- des tests d’intégration “anti fuite cross-tenant”.

---

## 8) Démonstration d’isolation cross-tenant

Nous avons validé manuellement :

- création d’une offre sous `TENANT_A`
- création d’une offre sous `TENANT_B`
- listing sous A ne renvoie que A
- listing sous B ne renvoie que B

Exemple de tenant B utilisé durant la vérification :
- `01J000000000000000000000000`

✅ Résultat : isolation fonctionnelle prouvée.

---

## 9) Alignement DDD + Symfony 8 : où on en est (et ce qu’on va améliorer)

### 9.1. Ce qu’on respecte déjà
- **Séparation des couches** :
    - `Domain` : concepts (TenantOwned)
    - `Application` : context (TenantContext), VO (TenantId)
    - `Interface` : HTTP subscribers + controllers
    - `Infrastructure` : Doctrine filter
- **DTO** pour inputs HTTP + validation
- **Autowiring** et conventions Symfony 8

### 9.2. Ce qui est volontairement “pragmatique” pour l’instant
- `Offer` est une entité Doctrine dans `src/Entity`.
    - C’est pratique pour valider rapidement le multi-tenant + migrations + endpoints.

### 9.3. Prochaine évolution (DDD plus strict)
Sur la prochaine étape, on introduira :
- un modèle `Domain` (agrégat Offer, VO, invariants),
- des **Use-cases** dans `Application` (Commands/Queries + DTO),
- des **ports** (interfaces Repository côté Application),
- des **adaptateurs** Doctrine côté Infrastructure.

Objectif : l’HTTP et Doctrine deviennent des détails d’implémentation.

---

## 10) Fichiers concernés (références)
- `src/Domain/Tenant/TenantOwned.php`
- `src/Infrastructure/Doctrine/Filter/TenantFilter.php`
- `config/packages/doctrine.yaml`
- `src/Interface/Http/Tenant/TenantDoctrineFilterSubscriber.php`
- `src/Entity/Offer.php`
- `src/Interface/Http/Api/Dto/CreateOfferRequest.php`
- `src/Controller/Api/OfferController.php`

---

## 11) Prochaine étape (roadmap immédiate)
1. Tests automatisés (PHPUnit) :
    - `X-Tenant` requis
    - cross-tenant read isolation
2. Structuration DDD “pure” :
    - Use-cases (CreateOffer, ListOffers)
    - Repository port + adapter Doctrine
    - mapping DTO ↔ Domain
3. Workflow Offer (draft → review → approved → published) + audit
4. Messenger/RabbitMQ (async) : import CSV, indexation ES, mailer