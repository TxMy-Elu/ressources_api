<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311233347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ressource ADD createur_id INT NOT NULL, ADD category_id INT NOT NULL');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F454473A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F454412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_939F454473A201E5 ON ressource (createur_id)');
        $this->addSql('CREATE INDEX IDX_939F454412469DE2 ON ressource (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F454473A201E5');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F454412469DE2');
        $this->addSql('DROP INDEX IDX_939F454473A201E5 ON ressource');
        $this->addSql('DROP INDEX IDX_939F454412469DE2 ON ressource');
        $this->addSql('ALTER TABLE ressource DROP createur_id, DROP category_id');
    }
}
