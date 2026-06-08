<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427183923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, rating INT NOT NULL, created_at DATETIME NOT NULL, author_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_9474526CF675F31B (author_id), INDEX IDX_9474526C89329D25 (resource_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used TINYINT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token), INDEX IDX_6B7BA4B6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_favorites (user_id INT NOT NULL, ressource_id INT NOT NULL, INDEX IDX_E489ED11A76ED395 (user_id), INDEX IDX_E489ED11FC6CD52A (ressource_id), PRIMARY KEY (user_id, ressource_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C89329D25 FOREIGN KEY (resource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_E489ED11A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_E489ED11FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C89329D25');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_E489ED11A76ED395');
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_E489ED11FC6CD52A');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE user_favorites');
    }
}
