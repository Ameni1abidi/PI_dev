<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add utilisateur.created_at and create ressource_quiz table.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $utilisateurTable = $schemaManager->introspectTable('utilisateur');
        if (!$utilisateurTable->hasColumn('created_at')) {
            $this->addSql("ALTER TABLE utilisateur ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            $this->addSql('UPDATE utilisateur SET created_at = NOW() WHERE created_at IS NULL');
            $this->addSql("ALTER TABLE utilisateur CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        if (!$schemaManager->tablesExist(['ressource_quiz'])) {
            $this->addSql('CREATE TABLE ressource_quiz (id INT AUTO_INCREMENT NOT NULL, ressource_id INT NOT NULL, type VARCHAR(12) NOT NULL, question VARCHAR(255) NOT NULL, choices JSON DEFAULT NULL, answer_hint LONGTEXT DEFAULT NULL, position INT NOT NULL, INDEX IDX_8A41E95AFC6CD52A (ressource_id), UNIQUE INDEX uniq_ressource_quiz_position (ressource_id, position), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE ressource_quiz ADD CONSTRAINT FK_8A41E95AFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['ressource_quiz'])) {
            $this->addSql('DROP TABLE ressource_quiz');
        }

        $utilisateurTable = $schemaManager->introspectTable('utilisateur');
        if ($utilisateurTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE utilisateur DROP created_at');
        }
    }
}
