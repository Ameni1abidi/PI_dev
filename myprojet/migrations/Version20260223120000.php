<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status and is_blocked fields to utilisateur for approval and blocking workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE utilisateur ADD status VARCHAR(20) NOT NULL DEFAULT 'PENDING'");
        $this->addSql('ALTER TABLE utilisateur ADD is_blocked TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql("UPDATE utilisateur SET status = 'APPROVED' WHERE status = 'PENDING'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP status');
        $this->addSql('ALTER TABLE utilisateur DROP is_blocked');
    }
}

