-- ─────────────────────────────────────────────────────────────────────────────
-- Initialisation MySQL — Création des bases de données
-- Exécuté une seule fois au premier démarrage du conteneur
-- ─────────────────────────────────────────────────────────────────────────────

-- Base développement
CREATE DATABASE IF NOT EXISTS resources
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Base tests (PHPUnit)
CREATE DATABASE IF NOT EXISTS resources_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Base pré-prod (utilisée par docker-compose.preprod.yaml)
CREATE DATABASE IF NOT EXISTS resources_preprod
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Accorder tous les droits à l'utilisateur app
GRANT ALL PRIVILEGES ON resources.*         TO 'app'@'%';
GRANT ALL PRIVILEGES ON resources_test.*    TO 'app'@'%';
GRANT ALL PRIVILEGES ON resources_preprod.* TO 'app'@'%';

-- Utilisateur pour le mysqld-exporter Prometheus (droits minimaux, lecture seule)
CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY 'exporter';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';

FLUSH PRIVILEGES;
