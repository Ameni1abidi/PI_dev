<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228232225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cours_student (cours_id INT NOT NULL, student_id INT NOT NULL, INDEX IDX_F425C6487ECF78B0 (cours_id), INDEX IDX_F425C648CB944F1A (student_id), PRIMARY KEY (cours_id, student_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE devoir_ia (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, niveau_difficulte VARCHAR(20) NOT NULL, duree INT NOT NULL, date_echeance DATE DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, contenu_json LONGTEXT NOT NULL, nb_qcm INT NOT NULL, nb_vrai_faux INT NOT NULL, nb_reponse_courte INT NOT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, cours_id INT NOT NULL, enseignant_id INT NOT NULL, INDEX IDX_B5E9A7C17ECF78B0 (cours_id), INDEX IDX_B5E9A7C1E455FCC0 (enseignant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE devoir_ia_reponse (id INT AUTO_INCREMENT NOT NULL, reponses_json LONGTEXT NOT NULL, note NUMERIC(5, 2) NOT NULL, feedback LONGTEXT DEFAULT NULL, date_soumission DATETIME NOT NULL, devoir_id INT NOT NULL, eleve_id INT NOT NULL, INDEX IDX_F69258DDC583534E (devoir_id), INDEX IDX_F69258DDA6CC7B2 (eleve_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, niveau VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student_chapitre_progress (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME DEFAULT NULL, last_viewed_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, time_spent_seconds INT NOT NULL, utilisateur_id INT NOT NULL, chapitre_id INT NOT NULL, INDEX IDX_47B7FB90FB88E14F (utilisateur_id), INDEX IDX_47B7FB901FBEEF7B (chapitre_id), UNIQUE INDEX uniq_progress_user_chapitre (utilisateur_id, chapitre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cours_student ADD CONSTRAINT FK_F425C6487ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours_student ADD CONSTRAINT FK_F425C648CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE devoir_ia ADD CONSTRAINT FK_B5E9A7C17ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE devoir_ia ADD CONSTRAINT FK_B5E9A7C1E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE devoir_ia_reponse ADD CONSTRAINT FK_F69258DDC583534E FOREIGN KEY (devoir_id) REFERENCES devoir_ia (id)');
        $this->addSql('ALTER TABLE devoir_ia_reponse ADD CONSTRAINT FK_F69258DDA6CC7B2 FOREIGN KEY (eleve_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE student_chapitre_progress ADD CONSTRAINT FK_47B7FB90FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_chapitre_progress ADD CONSTRAINT FK_47B7FB901FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapitre ADD resume LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE cours ADD titre_traduit VARCHAR(255) DEFAULT NULL, ADD description_traduit LONGTEXT DEFAULT NULL, ADD badge VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE ressource ADD type VARCHAR(50) DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_ressource_quiz_position ON ressource_quiz');
        $this->addSql('ALTER TABLE ressource_quiz DROP FOREIGN KEY `FK_8A41E95AFC6CD52A`');
        $this->addSql('DROP INDEX idx_8a41e95afc6cd52a ON ressource_quiz');
        $this->addSql('CREATE INDEX IDX_54ADFA1BFC6CD52A ON ressource_quiz (ressource_id)');
        $this->addSql('ALTER TABLE ressource_quiz ADD CONSTRAINT `FK_8A41E95AFC6CD52A` FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur ADD telephone VARCHAR(30) DEFAULT NULL, ADD status VARCHAR(20) NOT NULL, ADD is_blocked TINYINT NOT NULL, ADD parent_id INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3727ACA70 FOREIGN KEY (parent_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1D1C63B3727ACA70 ON utilisateur (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cours_student DROP FOREIGN KEY FK_F425C6487ECF78B0');
        $this->addSql('ALTER TABLE cours_student DROP FOREIGN KEY FK_F425C648CB944F1A');
        $this->addSql('ALTER TABLE devoir_ia DROP FOREIGN KEY FK_B5E9A7C17ECF78B0');
        $this->addSql('ALTER TABLE devoir_ia DROP FOREIGN KEY FK_B5E9A7C1E455FCC0');
        $this->addSql('ALTER TABLE devoir_ia_reponse DROP FOREIGN KEY FK_F69258DDC583534E');
        $this->addSql('ALTER TABLE devoir_ia_reponse DROP FOREIGN KEY FK_F69258DDA6CC7B2');
        $this->addSql('ALTER TABLE student_chapitre_progress DROP FOREIGN KEY FK_47B7FB90FB88E14F');
        $this->addSql('ALTER TABLE student_chapitre_progress DROP FOREIGN KEY FK_47B7FB901FBEEF7B');
        $this->addSql('DROP TABLE cours_student');
        $this->addSql('DROP TABLE devoir_ia');
        $this->addSql('DROP TABLE devoir_ia_reponse');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE student_chapitre_progress');
        $this->addSql('ALTER TABLE chapitre DROP resume');
        $this->addSql('ALTER TABLE cours DROP titre_traduit, DROP description_traduit, DROP badge');
        $this->addSql('ALTER TABLE ressource DROP type');
        $this->addSql('ALTER TABLE ressource_quiz DROP FOREIGN KEY FK_54ADFA1BFC6CD52A');
        $this->addSql('CREATE UNIQUE INDEX uniq_ressource_quiz_position ON ressource_quiz (ressource_id, position)');
        $this->addSql('DROP INDEX idx_54adfa1bfc6cd52a ON ressource_quiz');
        $this->addSql('CREATE INDEX IDX_8A41E95AFC6CD52A ON ressource_quiz (ressource_id)');
        $this->addSql('ALTER TABLE ressource_quiz ADD CONSTRAINT FK_54ADFA1BFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3727ACA70');
        $this->addSql('DROP INDEX IDX_1D1C63B3727ACA70 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP telephone, DROP status, DROP is_blocked, DROP parent_id, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
