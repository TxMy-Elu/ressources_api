<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add lien column to ressource, change contenu to LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ressource ADD lien VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE ressource MODIFY contenu LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ressource DROP COLUMN lien');
        $this->addSql('ALTER TABLE ressource MODIFY contenu VARCHAR(255) DEFAULT NULL');
    }
}
