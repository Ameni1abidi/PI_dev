<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add examen and resultat tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE examen (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, date_examen DATE NOT NULL, duree INT NOT NULL, cours_id INT NOT NULL, enseignant_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat (id INT AUTO_INCREMENT NOT NULL, note NUMERIC(5, 2) NOT NULL, appreciation LONGTEXT DEFAULT NULL, eleve_id INT NOT NULL, examen_id INT NOT NULL, INDEX IDX_F9A7DDE19D1A6E7A (examen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_F9A7DDE19D1A6E7A FOREIGN KEY (examen_id) REFERENCES examen (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_F9A7DDE19D1A6E7A');
        $this->addSql('DROP TABLE resultat');
        $this->addSql('DROP TABLE examen');
    }
}
