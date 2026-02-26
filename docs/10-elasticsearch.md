# Elasticsearch + Search API

## Architecture de recherche

### Choix technologiques

- **Client ES officiel** : `elasticsearch/elasticsearch:^8.0`
- **Pas de bundle Symfony** : on préfère un contrôle fin + DDD
- **Pattern Repository** : SearchOfferRepository comme adaptateur
- **Async indexing** : Messenger + RabbitMQ

### Avantages de cette approche

1. **Découplage** : Client ES en infrastructure, business logic en application
2. **Testabilité** : Facile de mocker le client pour les tests
3. **Flexibilité** : Pas de contraintes Symfony, full control ES
4. **Performance** : Direct HTTP calls, optimisé

---

## Mapping & Analyzers

### Index `offers`
json { "settings": { "number_of_shards": 1, "number_of_replicas": 0, "analysis": { "analyzer": { "default": { "type": "standard", "stopwords": "_french_" } } } }, "mappings": { "properties": { "id": { "type": "keyword" }, "tenant_id": { "type": "keyword" }, "title": { "type": "text", "analyzer": "default", "fields": { "keyword": { "type": "keyword" } } }, "description": { "type": "text", "analyzer": "default" }, "tags": { "type": "keyword" }, "status": { "type": "keyword" }, "indexed_at": { "type": "date", "format": "strict_date_time" } } } }


---

## Flux d'indexation (Async)

OfferCreated (Domain Event) ↓ OfferEventSubscriber (dispatches IndexOfferMessage) ↓ RabbitMQ (Messenger) ↓ IndexOfferHandler (reads from DB, indexes in ES) ↓ SearchOfferRepository (queries ES)


---

## API Endpoints

### GET `/api/offers/search`

Paramètres :
- `q` : full-text query (title + description)
- `tags[]` : filter by tags (OR logic)
- `status` : draft|review|approved|published
- `page` : pagination (défaut: 1)
- `limit` : items par page (défaut: 20, max: 100)
- `sortBy` : _score|title|status (défaut: _score)
- `sortOrder` : asc|desc (défaut: desc)

Exemple :

bash GET /api/offers/search?q=laptop&tags[]=electronics&tags[]=new&status=published&page=1&limit=20


Réponse :

json { "items": , "pagination": { "total": 42, "page": 1, "limit": 20, "pages": 3 } }


---

## Normes DDD appliquées

### ElasticsearchClient (Infrastructure)

Responsabilité unique : communiquer avec ES (CRUD documents)

php client->indexOffer(offerId, tenantId,document); client->deleteOffer(offerId); client->search(tenantId, $query);


### OfferIndexer (Infrastructure)

Convertit une entité Doctrine → document ES.
php indexer->index(offerEntity); indexer->unindex(offerId);


### SearchOfferRepository (Infrastructure)

Recherche depuis ES, retourne des DTOs.


### SearchOfferRepository (Infrastructure)

Recherche depuis ES, retourne des DTOs.

php repository->search(SearchOfferFiltersfilters): array


### SearchOffers (Application)

Use-case : appel le repository, valide les filtres.

php $searchOffers->search(query, tags, status, page, limit, ...);


---

## Tests

### Unitaires
- **SearchOfferFilters** : validation des paramètres
- **SearchOfferRepository** : construction de queries ES

### Intégration
- **SearchOffers** : end-to-end avec testcontainers ES

### Contrats API
- Pagination correcte
- Filtres multi-tenant isolés
- Scores pertinents

``` markdown
# Elasticsearch + Search API — Implémentation complète

Étape 10 du projet MarketFlow. Après Workflow + Messenger, on ajoute la **recherche full-text** via Elasticsearch.

## 🎯 Objectifs atteints

✅ Client Elasticsearch configuré (factory pattern)
✅ Indexation asynchrone via Messenger/RabbitMQ
✅ Repository de recherche (pattern DDD)
✅ API REST `/api/offers/search` avec filtres multi-tenant
✅ Tests d'intégration (SearchOffers + contrôleur)
✅ Validation stricte des paramètres (page, limit, sortOrder)

---

## 🏗️ Architecture mise en place
```

Domain (OfferAggregate) ↓ Application (SearchOffers use-case) ↓ Infrastructure (SearchOfferRepository + ElasticsearchClient) ↓ Interface HTTP (OfferSearchController)```

### Flux de données : création → indexation
```

POST /api/offers (CreateOffer use-case) ↓
OfferAggregate::create() → OfferCreated (Domain Event) ↓
OfferEventSubscriber (souscripteur infrastructure) ↓
Dispatch IndexOfferMessage → RabbitMQ ↓
IndexOfferHandler (async consumer) ↓
ElasticsearchClient::indexOffer() ↓
Document dans index offers``` 

---

## 📂 Fichiers créés/modifiés

### Infrastructure (Elasticsearch)

- **`src/Infrastructure/Search/ElasticsearchClient.php`** : wrapper client ES
  - Responsabilités : CRUD documents, gestion index
  - Méthodes : `indexOffer()`, `deleteOffer()`, `search()`, `initializeIndex()`

- **`src/Infrastructure/Search/OfferIndexer.php`** : convertisseur entité → document
  - Transformation : Doctrine Entity → ES Document
  - Gère les champs à indexer (title, description, tags, status)

- **`src/Infrastructure/Search/SearchOfferRepository.php`** : adaptateur recherche
  - Construit les queries ES depuis les filtres
  - Retourne des DTOs (pas des entités brutes)

- **`src/Infrastructure/Search/ElasticsearchClientFactory.php`** : factory pattern
  - Crée le client Elasticsearch avec configuration
  - Isolé pour testabilité + injectabilité

### Application (Use-cases)

- **`src/Application/Offer/SearchOffers.php`** : orchestration recherche
  - Récupère tenant courant via TenantContext
  - Valide les filtres via SearchOfferFilters DTO
  - Appelle repository, retourne résultats

- **`src/Application/Offer/Dto/SearchOfferFilters.php`** : DTO paramètres
  - Validation stricte (page >= 1, limit 1-100, sortOrder asc|desc)
  - Immuable, pas de setters

- **`src/Application/Offer/Dto/SearchOfferResult.php`** : DTO résultats
  - Représentation d'une offre dans les résultats de recherche
  - Inclut le score de pertinence (ES)

### Messenger (Async)

- **`src/Infrastructure/Messenger/Message/IndexOfferMessage.php`** : message RabbitMQ
  - Transporte l'ID offre à indexer

- **`src/Infrastructure/Messenger/Handler/IndexOfferHandler.php`** : consumer
  - Récupère l'offre depuis DB
  - Appelle `OfferIndexer::index()`
  - Gère les retries + erreurs

- **`src/Infrastructure/Messenger/EventSubscriber/OfferEventSubscriber.php`** : hookeur événements
  - Écoute `OfferCreated` (domain event)
  - Dispatch message async pour indexation

### Interface HTTP

- **`src/Interface/Http/Controller/OfferSearchController.php`** : endpoint REST
  - Route : `GET /api/offers/search`
  - Paramètres : `q`, `tags[]`, `status`, `page`, `limit`, `sortBy`, `sortOrder`
  - Validation + mapping d'erreurs

### Tests

- **`tests/Api/OfferSearchControllerTest.php`** : tests API (5 tests)
  - Header X-Tenant requis
  - Paramètres validés (page, limit)
  - Pagination correcte
  - Résultats vides = OK

- **`tests/Application/SearchOffersTest.php`** : tests use-case (5 tests)
  - Validation SearchOfferFilters
  - Paramètres defaults
  - Exceptions levées correctement

---

## 🔧 Configuration

### `config/services.yaml`
```

yaml
Elasticsearch Client (factory)
Elastic\Elasticsearch\Client: factory: [ 'App\Infrastructure\Search\ElasticsearchClientFactory', 'create' ]
Infrastructure
App\Infrastructure\Search\ElasticsearchClient: arguments: [ '@Elastic\Elasticsearch\Client', '@logger' ] public: false
App\Infrastructure\Search\OfferIndexer: arguments: [ '@App\Infrastructure\Search\ElasticsearchClient', '@logger' ] public: false
App\Infrastructure\Search\SearchOfferRepository: arguments: [ '@App\Infrastructure\Search\ElasticsearchClient', '@logger' ] public: false
Application
App\Application\Offer\SearchOffers: arguments: - '@App\Infrastructure\Search\SearchOfferRepository' - '@App\Application\Tenant\TenantContext' public: false
Messenger
App\Infrastructure\Messenger\Handler\IndexOfferHandler: tags:    public: false
App\Infrastructure\Messenger\EventSubscriber\OfferEventSubscriber: tags:    public: false
Controller
App\Interface\Http\Controller\OfferSearchController: tags:    public: falselatex_unknown_taglatex_unknown_taglatex_unknown_tag```

---

## 📡 API Endpoint : GET `/api/offers/search`

### Paramètres

| Param | Type | Défaut | Description |
|-------|------|--------|-------------|
| `q` | string (opt) | null | Full-text query (title + description) |
| `tags[]` | array (opt) | [] | Filtrer par tags (OR logic) |
| `status` | string (opt) | null | draft \| review \| approved \| published |
| `page` | int (opt) | 1 | Numéro de page (>= 1) |
| `limit` | int (opt) | 20 | Items par page (1-100) |
| `sortBy` | string (opt) | _score | \_score \| title \| status |
| `sortOrder` | string (opt) | desc | asc \| desc |

### Headers requis
```

X-Tenant: {tenant-id} # ULID du tenant Accept: application/json``` 

### Exemples de requêtes

#### Recherche simple
```

bash curl -X GET "http://localhost/api/offers/search?q=laptop"
-H "X-Tenant: 01ARZ3NDEKTSV4RRFFQ69G5FAV"```

#### Recherche avec tags (OR)
```

bash curl -X GET "http://localhost/api/offers/search?tags[]=electronics&tags[]=new"
-H "X-Tenant: 01ARZ3NDEKTSV4RRFFQ69G5FAV"``` 

#### Recherche filtrée + paginée
```

bash curl -X GET "http://localhost/api/offers/search?q=gaming&status=published&page=2&limit=50&sortBy=title&sortOrder=asc"
-H "X-Tenant: 01ARZ3NDEKTSV4RRFFQ69G5FAV"```

#### Postman
```

GET http://localhost/api/offers/search?q=laptop&page=1&limit=20 Headers: X-Tenant: 01ARZ3NDEKTSV4RRFFQ69G5FAV Accept: application/json``` 

### Réponses

#### Succès (200)
```

json { "items":   , "pagination": { "total": 42, "page": 1, "limit": 20, "pages": 3 } }latex_unknown_tag```

#### Erreur : paramètres invalides (400)
```

json { "error": "Limit must be between 1 and 100" }``` 

#### Erreur : tenant header manquant (400)
```

json { "error": "Missing required header X-Tenant." }```

#### Erreur serveur (500)
```

json { "error": "Search failed" }``` 

---

## 🧪 Tests manuel

### Prérequis
```

bash
Services actifs
docker compose up -d
App en dev
cd backend && php -S 127.0.0.1:8000 -t public
Consumer Messenger (autre terminal)
cd backend && php bin/console messenger:consume async -vv```

### Scénario 1 : Créer une offre et la chercher
```

bash
1. Créer une offre
curl -X POST http://localhost:8000/api/offers
-H "X-Tenant: 01ARZ3NDEKTSV4RRFFQ69G5FAV"
-H "Content-Type: application/json"
-d '{"title":"Test Laptop"}'
2. Attendre 2-3s que Messenger la traite
sleep 3
3. Chercher
curl -X GET "http://localhost:8000/api/offers/search?q=test"
-H "X-Tenant: 01ARZ3NDEKTSV4RRFFQ69G5FAV"
Réponse : l'offre apparaît avec un score``` 

### Scénario 2 : Isolation multi-tenant
```

bash TENANT_A="01ARZ3NDEKTSV4RRFFQ69G5FA1" TENANT_B="01ARZ3NDEKTSV4RRFFQ69G5FA2"
Offre pour tenant A
curl -X POST http://localhost:8000/api/offers
-H "X-Tenant: $TENANT_A"
-H "Content-Type: application/json"
-d '{"title":"Offre A"}'
Offre pour tenant B
curl -X POST http://localhost:8000/api/offers
-H "X-Tenant: $TENANT_B"
-H "Content-Type: application/json"
-d '{"title":"Offre A"}'
sleep 3
Cherche avec tenant A : voit UNE offre
curl -X GET "http://localhost:8000/api/offers/search?q=offre"
-H "X-Tenant: $TENANT_A"
pagination.total = 1
Cherche avec tenant B : voit UNE offre (pas celle de A)
curl -X GET "http://localhost:8000/api/offers/search?q=offre"
-H "X-Tenant: $TENANT_B"
pagination.total = 1```

---

## 🔍 Index Elasticsearch : structure

### Mapping
```

json { "offers": { "mappings": { "properties": { "id": { "type": "keyword" }, "tenant_id": { "type": "keyword" }, "title": { "type": "text", "analyzer": "default", "fields": { "keyword": { "type": "keyword" } } }, "description": { "type": "text", "analyzer": "default" }, "tags": { "type": "keyword" }, "status": { "type": "keyword" }, "indexed_at": { "type": "date" } } } } }``` 

### Analyzers
```

json { "analysis": { "analyzer": { "default": { "type": "standard", "stopwords": "french" } } } }```

Normalisation French : "Les", "la", "de" sont ignorés.

---

## 📊 Normes DDD appliquées

### 1. Séparation des responsabilités

| Couche | Responsabilité |
|--------|---|
| **Domain** | OfferAggregate, OfferCreated (métier pur) |
| **Application** | SearchOffers (orchestration métier) |
| **Infrastructure** | ES Client, indexing, repository (détails tech) |
| **Interface** | HTTP parsing + mapping JSON |

### 2. DTOs (Data Transfer Objects)

- **SearchOfferFilters** : immuable, validation stricte
- **SearchOfferResult** : projection pour la réponse API

**Avantage** : pas de fuite d'entités via l'API.

### 3. Ports & Adaptateurs

- **Port** : `SearchOfferRepository` (interface métier)
- **Adaptateur** : `App\Infrastructure\Search\SearchOfferRepository` (Elasticsearch)

Si demain on switch vers PostgreSQL full-text, on crée juste un nouvel adaptateur.

### 4. Events → Async

- Domain Event (`OfferCreated`) publié
- Event Subscriber dispatch message
- Handler traite asynchrone

**Découplage** : le domain ne connaît pas Elasticsearch.

### 5. Validation au bon endroit
```

Controller (déserialisation) ↓ DTO SearchOfferFilters (validation métier) ↓ Use-case SearchOffers (applique logique) ↓ Repository (requête technique)``` 

---

## 🚀 Prochaines étapes

1. **GraphQL** : queries composées (Offer + tags + transitions)
2. **TagIterators** : règles polymorphes pour validation/normalisation
3. **Mistral AI** : suggestions tags, catégorisation assistée
4. **Audit trail** : qui/quoi/quand sur chaque événement

---

## 📖 Références

- Elasticsearch Official Docs : https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/
- DDD Distilled : https://www.domainlanguage.com/
- Symfony Search : https://symfony.com/
```
