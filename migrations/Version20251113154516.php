<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113154516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD date_naissance DATE DEFAULT NULL, ADD bio LONGTEXT DEFAULT NULL, ADD musique VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, ADD discussion VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, ADD animaux VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, ADD pauses_cafe VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, ADD fumeur VARCHAR(15) DEFAULT \'indifferent\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP date_naissance, DROP bio, DROP musique, DROP discussion, DROP animaux, DROP pauses_cafe, DROP fumeur');
    }
}
