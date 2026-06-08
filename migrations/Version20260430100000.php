<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'log_message : renomme message_id → comment_id et corrige la FK vers comment';
    }

    public function up(Schema $schema): void
    {
        $sm      = $this->connection->createSchemaManager();
        $columns = array_keys($sm->listTableColumns('log_message'));

        // ── Rien à faire si comment_id existe déjà ───────────────────────────
        if (in_array('comment_id', $columns, true)) {
            $this->write('  <info>comment_id déjà présent – migration ignorée.</info>');
            return;
        }

        // ── 1. Supprimer les FK portant sur message_id ────────────────────────
        foreach ($sm->listTableForeignKeys('log_message') as $fk) {
            if (in_array('message_id', $fk->getLocalColumns(), true)) {
                $this->addSql('ALTER TABLE log_message DROP FOREIGN KEY ' . $fk->getName());
            }
        }

        // ── 2. Supprimer les index portant sur message_id ─────────────────────
        foreach ($sm->listTableIndexes('log_message') as $index) {
            if (!$index->isPrimary() && in_array('message_id', $index->getColumns(), true)) {
                $this->addSql('ALTER TABLE log_message DROP INDEX ' . $index->getName());
            }
        }

        // ── 3. Renommer la colonne ────────────────────────────────────────────
        if (in_array('message_id', $columns, true)) {
            $this->addSql('ALTER TABLE log_message CHANGE message_id comment_id INT DEFAULT NULL');
        } else {
            // La colonne n'existe pas du tout : on l'ajoute
            $this->addSql('ALTER TABLE log_message ADD comment_id INT DEFAULT NULL');
        }

        // ── 4. Recréer l'index et la FK vers comment ──────────────────────────
        $this->addSql('ALTER TABLE log_message ADD INDEX IDX_LOGMSG_COMMENT (comment_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE log_message
                ADD CONSTRAINT FK_LOGMSG_COMMENT
                    FOREIGN KEY (comment_id) REFERENCES `comment` (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_message DROP FOREIGN KEY FK_LOGMSG_COMMENT');
        $this->addSql('ALTER TABLE log_message DROP INDEX IDX_LOGMSG_COMMENT');
        $this->addSql('ALTER TABLE log_message CHANGE comment_id message_id INT DEFAULT NULL');
    }
}
