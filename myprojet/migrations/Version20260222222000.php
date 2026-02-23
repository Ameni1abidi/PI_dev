<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222222000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cours.enseignant_id column/index/foreign key if missing.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['cours'])) {
            return;
        }

        $coursTable = $schemaManager->introspectTable('cours');

        if (!$coursTable->hasColumn('enseignant_id')) {
            $this->addSql('ALTER TABLE cours ADD enseignant_id INT DEFAULT NULL');
        }

        $coursTable = $schemaManager->introspectTable('cours');
        if (!$coursTable->hasIndex('IDX_FDCA8C9CE455FCC0')) {
            $this->addSql('CREATE INDEX IDX_FDCA8C9CE455FCC0 ON cours (enseignant_id)');
        }

        $hasForeignKey = false;
        foreach ($coursTable->getForeignKeys() as $foreignKey) {
            if (in_array('enseignant_id', $foreignKey->getLocalColumns(), true)) {
                $hasForeignKey = true;
                break;
            }
        }

        if (!$hasForeignKey) {
            $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES utilisateur (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['cours'])) {
            return;
        }

        $coursTable = $schemaManager->introspectTable('cours');

        foreach ($coursTable->getForeignKeys() as $foreignKey) {
            if (in_array('enseignant_id', $foreignKey->getLocalColumns(), true)) {
                $this->addSql(sprintf('ALTER TABLE cours DROP FOREIGN KEY %s', $foreignKey->getName()));
                break;
            }
        }

        $coursTable = $schemaManager->introspectTable('cours');
        if ($coursTable->hasIndex('IDX_FDCA8C9CE455FCC0')) {
            $this->addSql('DROP INDEX IDX_FDCA8C9CE455FCC0 ON cours');
        }

        $coursTable = $schemaManager->introspectTable('cours');
        if ($coursTable->hasColumn('enseignant_id')) {
            $this->addSql('ALTER TABLE cours DROP enseignant_id');
        }
    }
}

