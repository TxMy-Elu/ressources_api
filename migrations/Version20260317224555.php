<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317224555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // duplicate of Version20260317224531 — media column already added
    }

    public function down(Schema $schema): void
    {
        // nothing to undo — media column managed by Version20260317224531
    }
}
