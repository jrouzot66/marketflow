# MarketFlow — Socle Symfony 8 + Multi-tenant (X-Tenant / ULID) + DDD (première brique)

Ce document décrit **ce que nous venons de mettre en place**, pourquoi, et en quoi cela respecte à la fois :
- les **pratiques Symfony 8** (autowiring, attributs PHP, EventSubscriber, configuration par convention, DX moderne)
- une **logique DDD** (séparation des responsabilités, objet métier `TenantId`, “context” applicatif, pas de dépendance Domain/Application vers l’Infra/Symfony)

---

## 1) Résultat obtenu (fonctionnel)

Nous avons un endpoint API de test :

- `GET /api/ping`

Et un comportement multi-tenant strict :

- Toute requête vers `/api/*` **doit** fournir le header : `X-Tenant: <ULID>`
- Sans header `X-Tenant` → réponse **400 Bad Request**
- Avec un ULID valide → réponse **200** et la réponse contient le tenant courant (preuve que le tenant est bien résolu et propagé)

### Tests manuels (curl)

curl -i http://localhost:8080/api/ping doit retourner 400 Bad Request
curl -i -H "X-Tenant: 01HZZZZZZZZZZZZZZZZZZZZZZZ" http://localhost:8080/api/ping doit retourner 200 OK


---

## 2) Pourquoi `X-Tenant` + ULID ?

### Objectif
Assurer un **isolement strict des données** entre tenants dans une architecture multi-tenant “base partagée”.

### Choix techniques
- Le tenant est porté par le header **`X-Tenant`** :
    - simple à utiliser en dev
    - explicite pour les consommateurs d’API
    - compatible avec REST et GraphQL (même logique d’entrée)
- Le tenant est un **ULID** :
    - facile à manipuler
    - standard Symfony via `Symfony\Component\Uid\Ulid`
    - string-friendly dans les logs/corrélation (sans exposer de secrets)

> Remarque : plus tard, on pourra ajouter d’autres stratégies (JWT claim / subdomain), mais on a volontairement commencé par la variante la plus simple et robuste pour l’API.

---

## 3) Architecture : où se situe cette brique dans notre DDD

Nous avons introduit une première séparation “DDD-friendly” :

### `Application`
Contient le **modèle applicatif** et les “primitives” nécessaires aux cas d’usage.

- `App\Application\Tenant\TenantId`
    - Objet valeur (Value Object) représentant l’identifiant de tenant
    - Validé par construction via `Ulid::fromString()`
    - **Ne dépend pas** de Doctrine, ni d’une Entité, ni d’une base de données

- `App\Application\Tenant\TenantContext`
    - Conserve le tenant courant pour la durée de la requête
    - Expose `getTenantId()` (et échoue si non défini)
    - Implémente `ResetInterface` afin d’être sûr qu’il est “clean” sur des exécutions long-running (utile surtout pour des workers)

✅ Ce qu’on respecte (DDD) :
- `TenantId` est un **VO** : immutable, construit via une factory (`fromString`)
- `TenantContext` représente un **état applicatif**, pas un concept de domaine
- Aucune dépendance de `Application` vers `Interface` ou `Infrastructure`

Qu'est-ce qu'un Value Object :
- Un Value Object est un concept de la conception pilotée par le domaine (DDD). C'est un objet qui représente une valeur descriptive et qui est défini par ses attributs, et non par une identité unique.
  Les caractéristiques principales d'un Value Object sont :
  - Immutabilité : Une fois créé, il ne peut pas être modifié. Si une modification est nécessaire, un nouvel objet est créé.
  - Égalité structurelle : Deux Value Objects sont considérés comme égaux si tous leurs attributs sont identiques.
  - Auto-validation : Il se valide lui-même lors de sa création (comme ici, TenantId qui valide que la chaîne est un ULID valide).
  Dans votre cas, TenantId est un Value Object car il représente la valeur d'un identifiant de tenant, il est immuable et deux TenantId avec la même valeur ULID sont interchangeables.

### `Interface` (couche d’entrée : HTTP)
Contient l’adaptation aux protocoles (HTTP ici).

- `App\Interface\Http\Tenant\TenantRequestSubscriber`
    - Intercepte les requêtes HTTP (`kernel.request`)
    - Si la route correspond à `/api/*`, exige `X-Tenant`
    - Parse/valide le ULID
    - Écrit le résultat dans `TenantContext`

✅ Ce qu’on respecte (DDD + Clean Architecture) :
- la couche HTTP **ne fait pas de logique métier**, elle fait :
    - validation “transport”
    - mapping vers objets applicatifs
    - gestion d’erreurs HTTP (400, etc.)
- on n’injecte pas Request partout : on centralise la résolution du tenant

---

## 4) Pratiques Symfony 8 utilisées

### EventSubscriber (Symfony EventDispatcher)
- Utilisation de `EventSubscriberInterface` pour brancher notre logique sur `KernelEvents::REQUEST`
- Usage de `RequestEvent::isMainRequest()` pour éviter d’impacter les sous-requêtes
- Priorité haute (100) pour résoudre le tenant tôt, avant la plupart des handlers

### Autowiring / Autoconfigure
- Les classes sont découvertes comme services par convention (namespace `src/`)
- `TenantContext` est injectable directement dans les contrôleurs

### Attributs PHP pour le routing
- Contrôleur `PingController` déclaré via l’attribut `#[Route(...)]`
- Conforme à la direction Symfony moderne (moins de YAML pour le routing quand c’est simple)

### DX front Symfony 8 (en place côté projet)
- Nous utilisons la stack moderne : AssetMapper + Symfony UX (Turbo/Stimulus)
- Important : cela n’empêche pas l’API de rester une API pure (JSON), au contraire

---

## 5) Comportement actuel et limites connues (volontaires)

### Actuel
- Le tenant est **obligatoire** sur `/api/*`
- `TenantRequestSubscriber` ne whitelist que :
    - hors `/api` : pas de tenant requis

### À faire ensuite (prévu)
- Exclure proprement certains endpoints (ex: `/api/docs`) si on ajoute OpenAPI
- Introduire un format d’erreur standard pour l’API (ex: `application/problem+json`)
- Ajouter des tests automatisés (PHPUnit) pour :
    - header manquant
    - header invalide
    - header valide

---

## 6) Sécurité & secrets (rappel de norme)
- Le header `X-Tenant` **n’est pas un secret** : c’est un identifiant (comme un “scope”).
- Les secrets (ex: clés API Mistral, mots de passe, etc.) doivent aller dans :
    - `.env.local` (dev, non commité)
    - Symfony Secrets / vault en prod
- Ne jamais committer de valeurs sensibles : utiliser des placeholders dans la doc et les exemples.

---

## 7) Pourquoi on commence par ça (ordre pragmatique)
Le multi-tenant est une contrainte **transversale** :
- DB (filtrage `tenant_id`)
- recherche (Elasticsearch filter)
- async (messages doivent transporter le tenant)
- audit (log par tenant)
- API REST + GraphQL

Le résoudre **tôt** évite de devoir refactorer tout le projet plus tard.

---

## 8) Prochaine étape (roadmap immédiate)
Une fois ce socle validé, on enchaîne sur :

1. **Persistance tenant-aware**
    - Choix stockage `tenant_id` en PostgreSQL (recommandation : colonne `uuid` via ULID→RFC4122, ou stockage texte si on veut rester ULID-string)
    - Migrations Doctrine

2. **Doctrine Filter multi-tenant**
    - Filtrage automatique `tenant_id = :tenantId` sur toutes les entités “tenant-owned”
    - Garde-fous anti-fuite (tests cross-tenant)

3. **Première verticale DDD : Offers / Tags**
    - Domain : agrégat Offer + VO
    - Application : use-cases + DTO
    - Interface : endpoints REST
    - Infrastructure : repositories Doctrine + DQL/QueryBuilder + SQL bulk (plus tard)

---

## 9) Fichiers concernés (références)
- `src/Application/Tenant/TenantId.php`
- `src/Application/Tenant/TenantContext.php`
- `src/Interface/Http/Tenant/TenantRequestSubscriber.php`
- `src/Controller/Api/PingController.php`

---

## 10) Convention multi-tenant (à retenir)
- Header : `X-Tenant`
- Format : ULID string
- Politique : obligatoire sur `/api/*`
- Propagation : via `TenantContext` (injectable partout)