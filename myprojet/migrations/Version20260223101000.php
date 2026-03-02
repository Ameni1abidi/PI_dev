<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223101000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add devoir_ia_reponse table for student submissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS devoir_ia_reponse (id INT AUTO_INCREMENT NOT NULL, devoir_id INT NOT NULL, eleve_id INT NOT NULL, reponses_json LONGTEXT NOT NULL, note NUMERIC(5, 2) NOT NULL, feedback LONGTEXT DEFAULT NULL, date_soumission DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6833896EA1266F45 (devoir_id), INDEX IDX_6833896E20EBA159 (eleve_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS devoir_ia_reponse');
    }
}
