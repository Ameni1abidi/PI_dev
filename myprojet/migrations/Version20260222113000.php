<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at column to utilisateur for admin analytics and copilot insights';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE utilisateur ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('UPDATE utilisateur SET created_at = NOW() WHERE created_at IS NULL');
        $this->addSql("ALTER TABLE utilisateur CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP created_at');
    }
}