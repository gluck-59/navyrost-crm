<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251112173200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CREATE TABLE payment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, request_id INT NOT NULL, type TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'0 — накладные, 1 — работы, 2 — (авторазноска) работа, 3 (авторазноска) накладные (авторазноски пока нет)\', sum INT DEFAULT 0 NOT NULL, created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', note VARCHAR(14) DEFAULT NULL, INDEX idx_payment_request (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840BDCEC74C2 FOREIGN KEY (request_id) REFERENCES request (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment');
    }
}
