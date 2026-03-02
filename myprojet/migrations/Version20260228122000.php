<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228122000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair schema drift: add missing ressource columns';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['ressource'])) {
            return;
        }

        $ressource = $schemaManager->introspectTable('ressource');

        if (!$ressource->hasColumn('type')) {
            $this->addSql('ALTER TABLE ressource ADD type VARCHAR(50) DEFAULT NULL');
        }

        if (!$ressource->hasColumn('available_at')) {
            $this->addSql('ALTER TABLE ressource ADD available_at DATETIME DEFAULT NULL');
        }

        if (!$ressource->hasColumn('nb_vues')) {
            $this->addSql('ALTER TABLE ressource ADD nb_vues INT NOT NULL DEFAULT 0');
        }

        if (!$ressource->hasColumn('nb_likes')) {
            $this->addSql('ALTER TABLE ressource ADD nb_likes INT NOT NULL DEFAULT 0');
        }

        if (!$ressource->hasColumn('nb_favoris')) {
            $this->addSql('ALTER TABLE ressource ADD nb_favoris INT NOT NULL DEFAULT 0');
        }

        if (!$ressource->hasColumn('score')) {
            $this->addSql('ALTER TABLE ressource ADD score INT NOT NULL DEFAULT 0');
        }

        if (!$ressource->hasColumn('badge')) {
            $this->addSql("ALTER TABLE ressource ADD badge VARCHAR(20) NOT NULL DEFAULT 'Moyen'");
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['ressource'])) {
            return;
        }

        $ressource = $schemaManager->introspectTable('ressource');

        if ($ressource->hasColumn('badge')) {
            $this->addSql('ALTER TABLE ressource DROP badge');
        }

        if ($ressource->hasColumn('score')) {
            $this->addSql('ALTER TABLE ressource DROP score');
        }

        if ($ressource->hasColumn('nb_favoris')) {
            $this->addSql('ALTER TABLE ressource DROP nb_favoris');
        }

        if ($ressource->hasColumn('nb_likes')) {
            $this->addSql('ALTER TABLE ressource DROP nb_likes');
        }

        if ($ressource->hasColumn('nb_vues')) {
            $this->addSql('ALTER TABLE ressource DROP nb_vues');
        }

        if ($ressource->hasColumn('available_at')) {
            $this->addSql('ALTER TABLE ressource DROP available_at');
        }

        if ($ressource->hasColumn('type')) {
            $this->addSql('ALTER TABLE ressource DROP type');
        }
    }
}
