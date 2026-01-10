<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260110082845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dispute: ensure reporter_tokens_paid column exists (safe noop)';
    }

    public function up(Schema $schema): void
    {
        // Colonne déjà présente → migration volontairement neutre
    }

    public function down(Schema $schema): void
    {
        // Aucun rollback
    }
}
