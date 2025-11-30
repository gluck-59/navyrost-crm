<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109214900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CREATE TABLE request';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE request (
            id INT AUTO_INCREMENT NOT NULL,
            equipment_id INT NOT NULL,
            customer_id INT NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 0 COMMENT "0: новая, 1: в работе; 2: готово",
            created DATETIME NOT NULL,
            updated DATETIME NOT NULL,
            name VARCHAR(255) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_3B978F9F517FE9FE (equipment_id),
            INDEX IDX_3B978F9F9395C3F3 (customer_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9F517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9F9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE request');
    }
}
