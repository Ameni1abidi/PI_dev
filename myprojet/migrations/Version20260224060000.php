<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure cours translated columns exist (titre_traduit, description_traduit)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('cours')) {
            return;
        }

        $coursTable = $schema->getTable('cours');

        if (!$coursTable->hasColumn('titre_traduit')) {
            $this->addSql('ALTER TABLE cours ADD titre_traduit VARCHAR(255) DEFAULT NULL');
        }

        if (!$coursTable->hasColumn('description_traduit')) {
            $this->addSql('ALTER TABLE cours ADD description_traduit LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('cours')) {
            return;
        }

        $coursTable = $schema->getTable('cours');

        if ($coursTable->hasColumn('description_traduit')) {
            $this->addSql('ALTER TABLE cours DROP description_traduit');
        }

        if ($coursTable->hasColumn('titre_traduit')) {
            $this->addSql('ALTER TABLE cours DROP titre_traduit');
        }
    }
}
