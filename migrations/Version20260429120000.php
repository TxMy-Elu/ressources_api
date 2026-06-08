<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables de logs : log_connexion, log_systeme, log_action';
    }

    public function up(Schema $schema): void
    {
        // log_connexion
        $this->addSql(<<<'SQL'
            CREATE TABLE log_connexion (
                id          INT AUTO_INCREMENT NOT NULL,
                user_id     INT          DEFAULT NULL,
                statut      VARCHAR(20)  NOT NULL,
                ip_address  VARCHAR(45)  DEFAULT NULL,
                date_connexion DATETIME  NOT NULL,
                INDEX IDX_LOG_CONN_USER (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE log_connexion
                ADD CONSTRAINT FK_LOG_CONN_USER
                FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE SET NULL
        SQL);

        // log_systeme
        $this->addSql(<<<'SQL'
            CREATE TABLE log_systeme (
                id       INT AUTO_INCREMENT NOT NULL,
                niveau   VARCHAR(20)  NOT NULL,
                message  VARCHAR(255) NOT NULL,
                contexte VARCHAR(255) DEFAULT NULL,
                date_log DATETIME     NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // log_action
        $this->addSql(<<<'SQL'
            CREATE TABLE log_action (
                id           INT AUTO_INCREMENT NOT NULL,
                user_id      INT          DEFAULT NULL,
                ressource_id INT          DEFAULT NULL,
                action       VARCHAR(100) NOT NULL,
                details      VARCHAR(255) DEFAULT NULL,
                ip_address   VARCHAR(45)  DEFAULT NULL,
                date_action  DATETIME     NOT NULL,
                INDEX IDX_LOG_ACT_USER (user_id),
                INDEX IDX_LOG_ACT_RES  (ressource_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE log_action
                ADD CONSTRAINT FK_LOG_ACT_USER
                FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE SET NULL,
                ADD CONSTRAINT FK_LOG_ACT_RES
                FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_action DROP FOREIGN KEY FK_LOG_ACT_USER');
        $this->addSql('ALTER TABLE log_action DROP FOREIGN KEY FK_LOG_ACT_RES');
        $this->addSql('ALTER TABLE log_connexion DROP FOREIGN KEY FK_LOG_CONN_USER');
        $this->addSql('DROP TABLE IF EXISTS log_action');
        $this->addSql('DROP TABLE IF EXISTS log_systeme');
        $this->addSql('DROP TABLE IF EXISTS log_connexion');
    }
}
