<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair schema drift: add cours.badge if missing';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['cours'])) {
            $cours = $schemaManager->introspectTable('cours');
            if (!$cours->hasColumn('badge')) {
                $this->addSql('ALTER TABLE cours ADD badge VARCHAR(20) DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['cours'])) {
            $cours = $schemaManager->introspectTable('cours');
            if ($cours->hasColumn('badge')) {
                $this->addSql('ALTER TABLE cours DROP badge');
            }
        }
    }
}

