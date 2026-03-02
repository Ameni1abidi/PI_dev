<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222010426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deprecated duplicate full-schema snapshot. Intentionally no-op.';
    }

    public function up(Schema $schema): void
    {
        // No-op: replaced by incremental migrations.
    }

    public function down(Schema $schema): void
    {
        // No-op.
    }
}
