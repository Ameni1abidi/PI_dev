<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout metadonnees Cloudinary dans ressource (public_id, resource_type).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ressource');
        if (!$table->hasColumn('cloudinary_public_id')) {
            $this->addSql('ALTER TABLE ressource ADD cloudinary_public_id VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('cloudinary_resource_type')) {
            $this->addSql('ALTER TABLE ressource ADD cloudinary_resource_type VARCHAR(20) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('ressource');
        if ($table->hasColumn('cloudinary_public_id')) {
            $this->addSql('ALTER TABLE ressource DROP cloudinary_public_id');
        }
        if ($table->hasColumn('cloudinary_resource_type')) {
            $this->addSql('ALTER TABLE ressource DROP cloudinary_resource_type');
        }
    }
}

