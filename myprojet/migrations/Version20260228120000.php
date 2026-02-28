<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair schema drift: utilisateur columns + resource interaction tables';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['utilisateur'])) {
            $utilisateur = $schemaManager->introspectTable('utilisateur');

            if (!$utilisateur->hasColumn('status')) {
                $this->addSql("ALTER TABLE utilisateur ADD status VARCHAR(20) NOT NULL DEFAULT 'PENDING'");
            }

            if (!$utilisateur->hasColumn('is_blocked')) {
                $this->addSql('ALTER TABLE utilisateur ADD is_blocked TINYINT(1) NOT NULL DEFAULT 0');
            }

            if (!$utilisateur->hasColumn('created_at')) {
                $this->addSql("ALTER TABLE utilisateur ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
                $this->addSql('UPDATE utilisateur SET created_at = NOW() WHERE created_at IS NULL');
                $this->addSql("ALTER TABLE utilisateur CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }
        }

        if (!$schemaManager->tablesExist(['ressource_like'])) {
            $this->addSql('CREATE TABLE ressource_like (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, ressource_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_5CDC403AFC6CD52A (ressource_id), INDEX IDX_5CDC403AFB88E14F (utilisateur_id), UNIQUE INDEX uniq_ressource_like_user (ressource_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!$schemaManager->tablesExist(['ressource_favori'])) {
            $this->addSql('CREATE TABLE ressource_favori (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, ressource_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_5C47171FC6CD52A (ressource_id), INDEX IDX_5C47171FB88E14F (utilisateur_id), UNIQUE INDEX uniq_ressource_favori_user (ressource_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!$schemaManager->tablesExist(['ressource_interaction'])) {
            $this->addSql('CREATE TABLE ressource_interaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, ressource_id INT NOT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_3EF2DBE4FC6CD52A (ressource_id), INDEX IDX_3EF2DBE4FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ressource_interaction');
        $this->addSql('DROP TABLE IF EXISTS ressource_favori');
        $this->addSql('DROP TABLE IF EXISTS ressource_like');
    }
}
