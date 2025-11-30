<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251108202558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ALTER TABLE customer';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

//        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E0961220EA6 FOREIGN KEY (creator) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_81398E0961220EA6 ON customer (creator_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E0961220EA6');
        $this->addSql('DROP INDEX IDX_81398E0961220EA6 ON customer');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E0961220EA6 FOREIGN KEY (creator_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
