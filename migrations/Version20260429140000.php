<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables : participation, progression, log_message';
    }

    public function up(Schema $schema): void
    {
        // ── participation ──────────────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE participation (
                id                 INT AUTO_INCREMENT NOT NULL,
                user_id            INT          NOT NULL,
                ressource_id       INT          NOT NULL,
                date_participation DATETIME     NOT NULL,
                mise_cote          TINYINT(1)   NOT NULL DEFAULT 0,
                invite             TINYINT(1)   NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_participation_user_ressource (user_id, ressource_id),
                INDEX IDX_PART_USER (user_id),
                INDEX IDX_PART_RES  (ressource_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE participation
                ADD CONSTRAINT FK_PART_USER
                    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_PART_RES
                    FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE
        SQL);

        // ── progression ────────────────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE progression (
                id           INT AUTO_INCREMENT NOT NULL,
                user_id      INT        NOT NULL,
                ressource_id INT        NOT NULL,
                statut       TINYINT(1) NOT NULL DEFAULT 0,
                updated_at   DATETIME   NOT NULL,
                UNIQUE INDEX uniq_progression_user_ressource (user_id, ressource_id),
                INDEX IDX_PROG_USER (user_id),
                INDEX IDX_PROG_RES  (ressource_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE progression
                ADD CONSTRAINT FK_PROG_USER
                    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_PROG_RES
                    FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE
        SQL);

        // ── log_message ────────────────────────────────────────────────────────
        // Trace les actions sur les commentaires (add, delete)
        // La FK pointe vers la table `comment` (entité Comment = message du MCD)
        $this->addSql(<<<'SQL'
            CREATE TABLE log_message (
                id          INT AUTO_INCREMENT NOT NULL,
                user_id     INT          DEFAULT NULL,
                comment_id  INT          DEFAULT NULL,
                action      VARCHAR(100) NOT NULL,
                details     VARCHAR(255) DEFAULT NULL,
                ip_address  VARCHAR(45)  DEFAULT NULL,
                date_action DATETIME     NOT NULL,
                INDEX IDX_LOGMSG_USER    (user_id),
                INDEX IDX_LOGMSG_COMMENT (comment_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE log_message
                ADD CONSTRAINT FK_LOGMSG_USER
                    FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE SET NULL,
                ADD CONSTRAINT FK_LOGMSG_COMMENT
                    FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_message DROP FOREIGN KEY FK_LOGMSG_USER');
        $this->addSql('ALTER TABLE log_message DROP FOREIGN KEY FK_LOGMSG_COMMENT');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_PROG_USER');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_PROG_RES');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_PART_USER');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_PART_RES');
        $this->addSql('DROP TABLE IF EXISTS log_message');
        $this->addSql('DROP TABLE IF EXISTS progression');
        $this->addSql('DROP TABLE IF EXISTS participation');
    }
}
