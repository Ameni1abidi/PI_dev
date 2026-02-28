<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add student chapter progress table for completion, prerequisites, and time tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE student_chapitre_progress (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, chapitre_id INT NOT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_viewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', time_spent_seconds INT NOT NULL, INDEX IDX_285A7DB5FB88E14F (utilisateur_id), INDEX IDX_285A7DB58C62B025 (chapitre_id), UNIQUE INDEX uniq_progress_user_chapitre (utilisateur_id, chapitre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE student_chapitre_progress ADD CONSTRAINT FK_285A7DB5FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_chapitre_progress ADD CONSTRAINT FK_285A7DB58C62B025 FOREIGN KEY (chapitre_id) REFERENCES chapitre (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student_chapitre_progress DROP FOREIGN KEY FK_285A7DB5FB88E14F');
        $this->addSql('ALTER TABLE student_chapitre_progress DROP FOREIGN KEY FK_285A7DB58C62B025');
        $this->addSql('DROP TABLE student_chapitre_progress');
    }
}
