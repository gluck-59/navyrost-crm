<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251108203118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ALTER TABLE customer';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_81398E09A977936C ON customer (parent_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09A977936C FOREIGN KEY (parent_id) REFERENCES customer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09A977936C');
        $this->addSql('DROP INDEX IDX_81398E09A977936C ON customer');
        $this->addSql('ALTER TABLE customer CHANGE parent_id parent_id INT NOT NULL');
    }
}
