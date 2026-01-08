<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260108105344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression du prix float et normalisation du coût en tokens (1..15)';
    }

    public function up(Schema $schema): void
    {
        // 🔥 Suppression définitive du prix float
        $this->addSql('ALTER TABLE trajet DROP COLUMN price');

        // 🪙 Normalisation du coût en tokens
        $this->addSql('ALTER TABLE trajet MODIFY token_cost INT NOT NULL DEFAULT 1');

        // 🛡 Sécurisation des données existantes
        $this->addSql('UPDATE trajet SET token_cost = 1 WHERE token_cost IS NULL OR token_cost < 1');
        $this->addSql('UPDATE trajet SET token_cost = 15 WHERE token_cost > 15');
    }

    public function down(Schema $schema): void
    {
        // ⚠️ rollback non prioritaire (dataset)
        $this->addSql('ALTER TABLE trajet ADD price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE trajet MODIFY token_cost INT NOT NULL DEFAULT 0');
    }
}
