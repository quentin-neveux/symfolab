<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260112174143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create city table only';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS city (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(200) NOT NULL,
            name_ascii VARCHAR(200) NOT NULL,
            country_code VARCHAR(2) NOT NULL,
            population INT DEFAULT NULL,
            lat NUMERIC(10, 7) NOT NULL,
            lon NUMERIC(10, 7) NOT NULL,
            INDEX idx_city_name (name),
            INDEX idx_city_country (country_code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE city');
    }
}
