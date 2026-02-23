
✅ Pratique Symfony : usage des fichiers `.env.*` pour séparer clairement dev/test/prod.

---

## 2) Outils installés (dev dependencies)

### 2.1 MakerBundle (dev)
Commande exécutée :
bash docker compose exec app sh -lc "cd /var/www/html && composer require symfony/maker-bundle --dev"


Rôle :
- accélérer la génération de code (entités, migrations, controllers, etc.) pendant le développement.

✅ Pratique Symfony : MakerBundle en `require-dev` uniquement.

### 2.2 Symfony Test Pack
Commande exécutée :
- bash docker compose exec app sh -lc "cd /var/www/html && composer require --dev symfony/test-pack"


Rôle :
- installer PHPUnit + intégration Symfony (KernelTestCase/WebTestCase, BrowserKit, etc.).

✅ Pratique Symfony 8 : adoption de `symfony/test-pack` comme base standard de tests.

---

## 3) Configuration Symfony en mode `test`

### 3.1 `framework.test: true`
Pour que `WebTestCase` puisse fonctionner correctement, Symfony doit activer le mode test :
- cela fournit `test.service_container` et les composants nécessaires au client de test.

Nous avons une configuration `when@test` dans `framework.yaml` qui active :
- `framework.test: true`
- une session mock.

✅ Pratique Symfony : configuration dédiée test, isolée, sans dépendre de services externes.

---

## 4) Doctrine en test : SQLite (raison et implications)

### 4.1 Pourquoi SQLite en tests ?
- rapidité d’exécution,
- tests reproductibles,
- pas besoin de lancer Postgres pour chaque run de test.

Nous utilisons :
- `DATABASE_URL=sqlite:///%kernel.project_dir%/var/test.db`

### 4.2 Point d’attention : types DB (uuid, ulid, etc.)
SQLite n’est pas Postgres : certains types/expressions peuvent diverger.
Dans notre cas, on a veillé à ce que le filtrage multi-tenant via Doctrine Filter reste compatible en test.

✅ Stratégie adoptée : privilégier un mapping compatible SQLite pour valider les invariants multi-tenant (et garder Postgres pour l’intégration “réelle” dev/prod).

---

## 5) Base de tests : reconstruction du schéma automatiquement

### 5.1 Classe helper de tests d’intégration
Nous avons créé un helper de tests :

- `backend/tests/Integration/DatabaseTestCase.php`

Rôle :
- supprimer (reset) `var/test.db` en amont,
- reconstruire le schéma Doctrine via `SchemaTool` depuis les métadonnées,
- garantir un état DB clean pour chaque test.

✅ Pratique : tests isolés et déterministes (pas d’ordre dépendant).

---

## 6) Tests automatisés multi-tenant

### 6.1 Test : `X-Tenant` requis sur `/api/ping`
Fichier :
- `backend/tests/Integration/Http/TenantHeaderTest.php`

Ce test valide :
- sans `X-Tenant` => `400`
- avec `X-Tenant` valide => `200`

✅ On transforme l’exigence multi-tenant en **contrat testable**.

### 6.2 Test : isolation cross-tenant sur Offers
Fichier :
- `backend/tests/Integration/Http/OffersMultiTenantIsolationTest.php`

Ce test valide :
- création d’une offer tenant A,
- création d’une offer tenant B,
- listing tenant A ne retourne que A,
- listing tenant B ne retourne que B.

✅ Anti-régression : si le filtre Doctrine ou les subscribers changent, on le verra immédiatement.

---

## 7) Commandes de validation

### 7.1 Lancer les tests
Commande exécutée :
- bash docker compose exec app sh -lc "cd /var/www/html && php bin/phpunit"


---

## 8) Ce que cela garantit (qualité et architecture)

### 8.1 Garanties multi-tenant
Avec ces tests en place, on garantit :
- aucune requête API `/api/*` ne passe sans tenant,
- un tenant ne peut pas lire les données d’un autre tenant via les endpoints testés.

### 8.2 Préparation DDD
Même si la verticale Offer est encore “pragmatique”, on a posé :
- un socle test,
- une contrainte transverse multi-tenant,
- une base saine pour refactor ensuite vers :
    - `Application` (use-cases),
    - `Domain` (agrégats / VO / invariants),
    - `Infrastructure` (adapters Doctrine),
      sans peur de casser le comportement.

---

## 9) Prochaine étape
À partir de maintenant, on peut avancer en confiance vers :
1. refactor DDD : `CreateOffer`, `ListOffers`, ports repository + adapters Doctrine,
2. introduction du Workflow Offer (draft → review → approved → published) + audit trail,
3. Messenger/RabbitMQ : async (emails, indexation, import, etc.),
4. Elasticsearch, GraphQL, etc.

Le tout en maintenant le niveau de qualité grâce à PHPUnit (puis Behat plus tard).