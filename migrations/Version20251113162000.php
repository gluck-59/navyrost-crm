<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251113162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[ДЛЯ ПУСТОЙ БАЗЫ] Создание администратора admin:admin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO user (username, roles, password) VALUES ("admin", \'["ROLE_ADMIN"]\', "$2y$12$aFeHrb6ixr4PFLLnJY0.weNIroB9K4TLboykvF7WkNpVno03aK7fy") ON DUPLICATE KEY UPDATE roles = VALUES(roles), password = VALUES(password)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user WHERE username = 'admin'");
    }
}
