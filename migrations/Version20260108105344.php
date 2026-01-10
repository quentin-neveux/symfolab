<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260108105344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression du prix float et normalisation du coût en tokens (1..15) — safe';
    }

    public function up(Schema $schema): void
    {
        // ✅ Suppression du prix float seulement si la colonne existe (évite "Can't DROP COLUMN")
        $hasPrice = (int) $this->connection->fetchOne("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'trajet'
              AND COLUMN_NAME = 'price'
        ");

        if ($hasPrice === 1) {
            $this->addSql('ALTER TABLE trajet DROP COLUMN price');
        }

        // 🪙 Normalisation du coût en tokens
        $this->addSql('ALTER TABLE trajet MODIFY token_cost INT NOT NULL DEFAULT 1');

        // 🛡 Sécurisation des données existantes
        $this->addSql('UPDATE trajet SET token_cost = 1 WHERE token_cost IS NULL OR token_cost < 1');
        $this->addSql('UPDATE trajet SET token_cost = 15 WHERE token_cost > 15');
    }

    public function down(Schema $schema): void
    {
        // ✅ Rollback safe aussi (ne recrée price que si elle n'existe pas)
        $hasPrice = (int) $this->connection->fetchOne("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'trajet'
              AND COLUMN_NAME = 'price'
        ");

        if ($hasPrice === 0) {
            $this->addSql('ALTER TABLE trajet ADD price DOUBLE PRECISION DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE trajet MODIFY token_cost INT NOT NULL DEFAULT 0');
    }
}
