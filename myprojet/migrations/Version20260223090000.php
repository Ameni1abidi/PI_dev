<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add devoir_ia table for AI-generated assignments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE devoir_ia (id INT AUTO_INCREMENT NOT NULL, cours_id INT NOT NULL, enseignant_id INT NOT NULL, titre VARCHAR(255) NOT NULL, niveau_difficulte VARCHAR(20) NOT NULL, duree INT NOT NULL, date_echeance DATE DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, contenu_json LONGTEXT NOT NULL, nb_qcm INT NOT NULL, nb_vrai_faux INT NOT NULL, nb_reponse_courte INT NOT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_AA5B1A7F8E5A2B30 (cours_id), INDEX IDX_AA5B1A7F8DC9B6B5 (enseignant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE devoir_ia ADD CONSTRAINT FK_AA5B1A7F8E5A2B30 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE devoir_ia ADD CONSTRAINT FK_AA5B1A7F8DC9B6B5 FOREIGN KEY (enseignant_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE devoir_ia DROP FOREIGN KEY FK_AA5B1A7F8E5A2B30');
        $this->addSql('ALTER TABLE devoir_ia DROP FOREIGN KEY FK_AA5B1A7F8DC9B6B5');
        $this->addSql('DROP TABLE devoir_ia');
    }
}
