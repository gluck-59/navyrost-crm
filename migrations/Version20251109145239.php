<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109145239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CREATE TABLE equipment';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, name VARCHAR(255) NOT NULL, mark VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, serial VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_D338D5839395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D5839395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09727ACA70 FOREIGN KEY (parent_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_81398E09727ACA70 ON customer (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_D338D5839395C3F3');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09727ACA70');
        $this->addSql('DROP INDEX IDX_81398E09727ACA70 ON customer');
    }
}
