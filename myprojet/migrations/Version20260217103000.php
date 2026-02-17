<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contenu field to examen';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE examen ADD contenu LONGTEXT DEFAULT NULL');
        $this->addSql("UPDATE examen SET contenu = '' WHERE contenu IS NULL");
        $this->addSql('ALTER TABLE examen MODIFY contenu LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE examen DROP COLUMN contenu');
    }
}
