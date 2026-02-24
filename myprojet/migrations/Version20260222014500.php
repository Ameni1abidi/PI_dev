<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222014500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add parent-child relation on utilisateur (one parent, many children)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3727ACA70 FOREIGN KEY (parent_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1D1C63B3727ACA70 ON utilisateur (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3727ACA70');
        $this->addSql('DROP INDEX IDX_1D1C63B3727ACA70 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP parent_id');
    }
}
