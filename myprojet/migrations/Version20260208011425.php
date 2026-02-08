<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208011425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(150) NOT NULL, type VARCHAR(50) NOT NULL, contenu LONGTEXT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_939F4544BCF5E72D (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(200) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(200) NOT NULL, role VARCHAR(200) NOT NULL, is_verified TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F4544BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE commentaire ADD forum_id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id)');
        $this->addSql('CREATE INDEX IDX_67F068BC29CCBAD0 ON commentaire (forum_id)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F4544BCF5E72D');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC29CCBAD0');
        $this->addSql('DROP INDEX IDX_67F068BC29CCBAD0 ON commentaire');
        $this->addSql('ALTER TABLE commentaire DROP forum_id');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
    }
}
