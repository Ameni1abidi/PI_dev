<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair schema drift: add chapitre.resume if missing';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['chapitre'])) {
            return;
        }

        $chapitre = $schemaManager->introspectTable('chapitre');

        if (!$chapitre->hasColumn('resume')) {
            $this->addSql('ALTER TABLE chapitre ADD resume LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['chapitre'])) {
            return;
        }

        $chapitre = $schemaManager->introspectTable('chapitre');

        if ($chapitre->hasColumn('resume')) {
            $this->addSql('ALTER TABLE chapitre DROP resume');
        }
    }
}
