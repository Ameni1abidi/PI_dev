<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206033446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Placeholder migration to register previously executed version';
    }

    public function up(Schema $schema): void
    {
        // Intentionally empty: this migration already ran in the database.
    }

    public function down(Schema $schema): void
    {
        // No-op.
    }
}
