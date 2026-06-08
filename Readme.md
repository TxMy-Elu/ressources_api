# (RE)Sources Relationnelles — API Backend

API REST développée avec **Symfony 8.0** pour la plateforme (RE)Sources Relationnelles du CESI. Elle gère les ressources éducatives et relationnelles avec authentification JWT, contrôle d'accès par rôles et workflow de modération.


---

## Table des matières

- [Démarrage rapide](#démarrage-rapide)
- [Configuration de l'environnement](#configuration-de-lenvironnement)
- [Architecture du projet](#architecture-du-projet)
- [Technologies utilisées](#technologies-utilisées)
- [Authentification & Sécurité](#authentification--sécurité)
- [Structure du projet](#structure-du-projet)
- [API — Endpoints implémentés](#api--endpoints-implémentés)
- [Workflow de modération](#workflow-de-modération)
- [Tests](#tests)
- [CI/CD](#cicd)
- [Comptes de test](#comptes-de-test)

---

## Démarrage rapide

### Prérequis

- [Docker](https://www.docker.com/) + Docker Compose
- Git

### Lancement en 5 étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/emraaah/resources.git
cd resources

# 2. Démarrer les conteneurs
docker-compose up -d

# 3. Entrer dans le conteneur PHP
docker exec -it resources_php bash

# 4. Installer les dépendances et initialiser la base
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 5. Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

L'API est disponible sur **http://localhost:8080**
La documentation Swagger est disponible sur **http://localhost:8080/api/doc**

---

## Configuration de l'environnement

### Services Docker

| Service     | URL                       | Identifiants              |
|-------------|---------------------------|---------------------------|
| API (nginx) | http://localhost:8080     | —                         |
| phpMyAdmin  | http://localhost:8081     | user: `app` / mdp: `app`  |
| MySQL       | localhost:3307            | user: `app` / mdp: `app`  |
| Xdebug      | port 9003                 | —                         |

### Variables d'environnement (`.env`)

```dotenv
APP_ENV=dev
DATABASE_URL="mysql://app:app@database:3306/resources?serverVersion=8.0&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=                           # vide en dev/test
MAILER_DSN=gmail://user:pass@default      # Gmail SMTP
FRONTEND_URL=http://localhost:3000
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

### Commandes utiles

```bash
# Cache
php bin/console cache:clear

# Base de données
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Clés JWT
php bin/console lexik:jwt:generate-keypair

# Générateurs Symfony
php bin/console make:entity
php bin/console make:migration

# Sauvegarde / restauration BDD
./scripts/db-backup.sh
./scripts/db-restore.sh backups/resources_20260501_120000.sql.gz
```

---

## Architecture du projet

```
┌─────────────────────────────────────┐
│         Client (Frontend)           │
│         React / Bruno               │
└──────────────┬──────────────────────┘
               │ HTTP/JSON
┌──────────────▼──────────────────────┐
│         nginx (port 8080)           │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│         PHP 8.4 / Symfony 8.0       │
│                                     │
│  ┌──────────────────────────────┐   │
│  │      Security Layer          │   │
│  │  JWT + Role Hierarchy        │   │
│  └──────────────┬───────────────┘   │
│                 │                   │
│  ┌──────────────▼───────────────┐   │
│  │       Controllers API        │   │
│  │  Auth / Resources / Admin    │   │
│  │  Moderation / User / Search  │   │
│  └──────────────┬───────────────┘   │
│                 │                   │
│  ┌──────────────▼───────────────┐   │
│  │    Services & Subscribers    │   │
│  │  LogService / Mailer         │   │
│  └──────────────┬───────────────┘   │
│                 │                   │
│  ┌──────────────▼───────────────┐   │
│  │     Doctrine ORM             │   │
│  │  Entities / Repositories     │   │
│  └──────────────┬───────────────┘   │
└─────────────────┼───────────────────┘
                  │
┌─────────────────▼───────────────────┐
│         MySQL 8.0                   │
│  resources (prod) / resources_test  │
└─────────────────────────────────────┘
```

### Hiérarchie des rôles

```
ROLE_SUPER_ADMIN
      ↑
 ROLE_ADMIN
      ↑
ROLE_MODERATOR
      ↑
ROLE_CONNECTED
      ↑
  ROLE_USER        ← rôle par défaut à l'inscription
```

---

## Technologies utilisées

| Catégorie        | Technologie                               | Version |
|------------------|-------------------------------------------|---------|
| Framework        | Symfony                                   | 8.0     |
| Langage          | PHP                                       | 8.4     |
| Base de données  | MySQL                                     | 8.0     |
| ORM              | Doctrine ORM                              | ^3.6    |
| Authentification | LexikJWTAuthenticationBundle              | ^3.2    |
| Documentation    | NelmioApiDocBundle (OpenAPI/Swagger)      | ^5.9    |
| CORS             | NelmioCorsBundle                          | *       |
| Annotations OA   | zircote/swagger-php                       | ^5.8    |
| Email            | Symfony Mailer + Google Mailer            | 8.0     |
| Tests            | PHPUnit                                   | ^13.1   |
| Analyse statique | PHPStan                                   | ^2.1    |
| Style de code    | PHP_CodeSniffer                           | ^4.0    |
| Serveur web      | nginx:alpine                              | —       |
| CI/CD            | GitHub Actions                            | —       |

---

## Authentification & Sécurité

### Flux JWT

```
POST /api/login  →  { email, password }
                 ←  { token: "eyJ..." }

Requêtes suivantes :
Authorization: Bearer eyJ...
```

### Endpoints publics (sans token)

- `POST /api/register` — inscription
- `POST /api/login` — connexion
- `POST /api/auth/forgot-password` — demande de reset
- `POST /api/auth/reset-password` — confirmation du reset
- `GET /api/resources` — liste publique des ressources
- `GET /api/categories` — liste des catégories
- `GET /api/resources/share/{token}` — accès par lien partagé
- `GET /api/doc` — documentation Swagger

### Matrice des accès

| Endpoint                          | Rôle minimum requis |
|-----------------------------------|---------------------|
| `/api/me`                         | ROLE_CONNECTED      |
| `/api/resources` (POST/PUT)       | ROLE_CONNECTED      |
| `/api/resources/mine`             | ROLE_CONNECTED      |
| `/api/resources/{id}/comments`    | ROLE_CONNECTED      |
| `/api/resources/{id}/save`        | ROLE_CONNECTED      |
| `/api/users/me/participations`    | ROLE_CONNECTED      |
| `/api/moderation/*`               | ROLE_MODERATOR      |
| `/api/users` (liste)              | ROLE_ADMIN          |
| `/api/admin/stats`                | ROLE_ADMIN          |
| `/api/admin/logs/*`               | ROLE_ADMIN          |
| `/api/admin/users/{id}/promote`   | ROLE_SUPER_ADMIN    |
| `/api/resources/{id}` (DELETE)    | ROLE_SUPER_ADMIN    |
| `/api/users/{id}` (DELETE)        | ROLE_SUPER_ADMIN    |

---

## Structure du projet

```
resources/
├── .github/
│   └── workflows/
│       └── ci.yml                      # Pipeline CI/CD GitHub Actions
├── config/
│   ├── jwt/                            # Clés RSA privée/publique
│   └── packages/
│       ├── security.yaml               # Firewalls, rôles, access_control
│       ├── nelmio_cors.yaml            # Configuration CORS
│       └── ...
├── migrations/                         # Migrations Doctrine (10 fichiers)
├── scripts/
│   ├── db-backup.sh                    # Sauvegarde BDD via Docker
│   └── db-restore.sh                   # Restauration BDD via Docker
├── src/
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── AdminController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── ContactController.php
│   │   │   ├── ModerationController.php
│   │   │   ├── ParticipationController.php
│   │   │   ├── ProgressionController.php
│   │   │   ├── ResourceController.php
│   │   │   ├── SearchController.php
│   │   │   └── UserController.php
│   │   ├── AuthController.php
│   │   └── ResetPasswordController.php
│   ├── Entity/
│   │   ├── Category.php
│   │   ├── Comment.php
│   │   ├── LogAction.php / LogConnexion.php / LogMessage.php / LogSysteme.php
│   │   ├── Participation.php
│   │   ├── PasswordResetToken.php
│   │   ├── Progression.php
│   │   ├── Ressource.php
│   │   └── User.php
│   ├── EventSubscriber/
│   │   └── ApiExceptionSubscriber.php  # Gestion globale des exceptions API
│   ├── Repository/                     # 11 repositories Doctrine
│   ├── Security/
│   │   └── UserChecker.php             # Vérification du statut utilisateur
│   └── Service/
│       ├── LogService.php              # Journalisation applicative
│       └── PasswordResetMailer.php     # Envoi d'emails de reset
├── templates/
│   └── reset_password/                 # Templates Twig (reset mot de passe)
├── tests/
│   ├── E2E/                            # Tests end-to-end (controllers)
│   │   ├── AuthControllerTest.php
│   │   ├── CategoryControllerTest.php
│   │   ├── ModerationControllerTest.php
│   │   └── ResourceControllerTest.php
│   ├── Regression/                     # Tests de non-régression
│   │   └── WorkflowRegressionTest.php
│   ├── Unit/                           # Tests unitaires
│   │   └── Entity/
│   │       ├── CategoryTest.php
│   │       ├── RessourceTest.php
│   │       └── UserTest.php
│   ├── TestCase/
│   │   └── ApiTestCase.php             # Classe de base avec helpers JWT
│   └── bootstrap.php
├── docker-compose.yaml
├── phpunit.dist.xml
└── .env
```

---

## API — Endpoints implémentés

### Authentification (`/api`)

| Méthode | Endpoint                      | Description                        | Accès    |
|---------|-------------------------------|------------------------------------|----------|
| POST    | `/api/register`               | Inscription d'un utilisateur       | Public   |
| POST    | `/api/login`                  | Connexion — retourne un token JWT  | Public   |
| GET     | `/api/me`                     | Profil de l'utilisateur connecté   | Connecté |
| POST    | `/api/logout`                 | Déconnexion                        | Connecté |
| POST    | `/api/auth/forgot-password`   | Demande de réinitialisation        | Public   |
| POST    | `/api/auth/reset-password`    | Réinitialisation du mot de passe   | Public   |
| POST    | `/api/contact`                | Envoi d'un message de contact      | Public   |

### Ressources (`/api/resources`)

| Méthode | Endpoint                           | Description                                | Accès          |
|---------|------------------------------------|--------------------------------------------|----------------|
| GET     | `/api/resources`                   | Liste des ressources publiées (filtrable)  | Public         |
| POST    | `/api/resources`                   | Créer une ressource                        | Connecté       |
| GET     | `/api/resources/mine`              | Mes ressources                             | Connecté       |
| GET     | `/api/resources/admin`             | Toutes les ressources (vue admin)          | Admin          |
| GET     | `/api/resources/trending`          | Ressources tendance                        | Public         |
| GET     | `/api/resources/share/{token}`     | Accès par lien partagé                     | Public         |
| GET     | `/api/resources/{id}`              | Détail d'une ressource                     | Public/Connecté|
| PUT     | `/api/resources/{id}`              | Modifier une ressource                     | Connecté       |
| DELETE  | `/api/resources/{id}`              | Supprimer une ressource                    | Super Admin    |
| GET     | `/api/resources/{id}/comments`     | Commentaires paginés                       | Public         |
| POST    | `/api/resources/{id}/comments`     | Ajouter un commentaire                     | Connecté       |
| POST    | `/api/resources/{id}/save`         | Sauvegarder en favori                      | Connecté       |

### Catégories (`/api/categories`)

| Méthode | Endpoint                   | Description              | Accès      |
|---------|----------------------------|--------------------------|------------|
| GET     | `/api/categories`          | Liste des catégories     | Public     |
| POST    | `/api/categories`          | Créer une catégorie      | Admin      |
| PUT     | `/api/categories/{id}`     | Modifier une catégorie   | Admin      |
| DELETE  | `/api/categories/{id}`     | Supprimer une catégorie  | Super Admin|

### Utilisateurs (`/api/users`)

| Méthode | Endpoint              | Description                     | Accès      |
|---------|-----------------------|---------------------------------|------------|
| GET     | `/api/users`          | Liste des utilisateurs (admin)  | Admin      |
| GET     | `/api/users/{id}`     | Profil d'un utilisateur         | Connecté   |
| PUT     | `/api/users/{id}`     | Modifier un utilisateur         | Connecté   |
| DELETE  | `/api/users/{id}`     | Supprimer un utilisateur        | Super Admin|

### Modération (`/api/moderation`)

| Méthode | Endpoint                           | Description                       | Accès      |
|---------|------------------------------------|-----------------------------------|------------|
| GET     | `/api/moderation/pending`          | Ressources en attente             | Modérateur |
| POST    | `/api/moderation/validate/{id}`    | Valider et publier une ressource  | Modérateur |
| POST    | `/api/moderation/suspend/{id}`     | Suspendre une ressource           | Modérateur |

### Administration (`/api/admin`)

| Méthode | Endpoint                           | Description                          | Accès      |
|---------|------------------------------------|--------------------------------------|------------|
| GET     | `/api/admin/stats`                 | Statistiques globales                | Admin      |
| GET     | `/api/admin/logs/connexion`        | Logs de connexion paginés            | Admin      |
| GET     | `/api/admin/logs/actions`          | Logs d'actions paginés               | Admin      |
| GET     | `/api/admin/logs/messages`         | Logs de messages paginés             | Admin      |
| GET     | `/api/admin/logs/systeme`          | Logs système paginés                 | Super Admin|
| PUT     | `/api/admin/users/{id}/promote`    | Promouvoir le rôle d'un utilisateur  | Super Admin|
| PUT     | `/api/admin/users/{id}/status`     | Activer / suspendre un compte        | Admin      |

### Participation & Progression

| Méthode | Endpoint                               | Description                    | Accès    |
|---------|----------------------------------------|--------------------------------|----------|
| GET     | `/api/resources/{id}/participation`    | Statut de participation        | Connecté |
| POST    | `/api/resources/{id}/participation`    | Enregistrer une participation  | Connecté |
| GET     | `/api/users/me/participations`         | Mes participations             | Connecté |
| GET     | `/api/resources/{id}/progression`      | Statut de progression          | Connecté |
| POST    | `/api/resources/{id}/progression`      | Mettre à jour la progression   | Connecté |
| GET     | `/api/users/me/progressions`           | Mes progressions               | Connecté |

### Recherche

| Méthode | Endpoint       | Description                          | Accès  |
|---------|----------------|--------------------------------------|--------|
| GET     | `/api/search`  | Recherche de ressources avec filtres | Public |

---

## Workflow de modération

```
Utilisateur soumet une ressource
         │
         ▼
   visibilite = "public"  ──→  statut = "en attente"  ──→  Modérateur
         │                                                       │
   visibilite = "private"                               ┌────────┴────────┐
         │                                              ▼                 ▼
         ▼                                          Valider          Suspendre
   statut = "brouillon"                                │                 │
                                                       ▼                 ▼
                                                  "publie"           "suspendu"
```

Les ressources publiques passent obligatoirement par la modération avant d'apparaître dans la liste publique.

---

## Tests

### Lancer les tests

```bash
# Depuis le conteneur PHP
docker exec -e APP_ENV=test resources_php php bin/phpunit

# Par dossier
docker exec -e APP_ENV=test resources_php php bin/phpunit tests/Unit
docker exec -e APP_ENV=test resources_php php bin/phpunit tests/E2E
docker exec -e APP_ENV=test resources_php php bin/phpunit tests/Regression

# Un seul fichier
docker exec -e APP_ENV=test resources_php php bin/phpunit tests/Unit/Entity/UserTest.php
```

### Organisation des tests

| Dossier                | Type             | Objectif                                               |
|------------------------|------------------|--------------------------------------------------------|
| `tests/Unit/`          | Unitaires        | Valider les entités et leur logique métier             |
| `tests/E2E/`           | End-to-end       | Tester les controllers via requêtes HTTP complètes     |
| `tests/Regression/`    | Non-régression   | Vérifier que les workflows critiques ne régressent pas |

### Classe de base `ApiTestCase`

Tous les tests d'intégration héritent de `ApiTestCase` qui fournit :

```php
$this->createAuthenticatedClient();     // Citoyen (ROLE_CONNECTED)
$this->createAdminClient();             // Admin (ROLE_SUPER_ADMIN)
$this->createModeratorClient();         // Modérateur (ROLE_MODERATOR)
$this->jsonResponse($client);           // Décode la réponse JSON
```

---

## CI/CD

Le pipeline GitHub Actions se déclenche à chaque **push** ou **pull request** sur `main`.

### Étapes du pipeline

```
1. Checkout du code
2. Démarrage de MySQL 8.0 (service GitHub Actions)
3. Installation de PHP 8.4 (extensions : intl, pdo_mysql, mbstring, openssl)
4. Cache des dépendances Composer
5. Installation des dépendances (composer install)
6. Génération des clés JWT
7. Création de la base de test + migrations
8. Chargement des fixtures
9. Exécution de PHPUnit
```

### Variables d'environnement CI

```dotenv
APP_ENV=test
DATABASE_URL=mysql://app:app@127.0.0.1:3306/resources_test
JWT_PASSPHRASE=          # vide
MAILER_DSN=null://null   # désactivé
```

---

## Comptes de test

Ces comptes sont créés automatiquement par les fixtures (`doctrine:fixtures:load`) :

| Email                      | Mot de passe  | Rôle             |
|----------------------------|---------------|------------------|
| `admin@resources.fr`       | `Admin1234!`  | ROLE_SUPER_ADMIN |
| `moderateur@resources.fr`  | `Modo1234!`   | ROLE_MODERATOR   |
| `citoyen@resources.fr`     | `User1234!`   | ROLE_CONNECTED   |

---

## Documentation API interactive

1. Démarrer les conteneurs : `docker-compose up -d`
2. Ouvrir : **http://localhost:8080/api/doc**
3. Cliquer sur **Authorize** et coller un token JWT pour tester les endpoints protégés

Après modification d'annotations `#[OA\...]` dans un controller :

```bash
php bin/console cache:clear
```
