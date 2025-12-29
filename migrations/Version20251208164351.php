<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208164351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aimlab_score (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, average_time DOUBLE PRECISION NOT NULL, played_at DATETIME NOT NULL, INDEX IDX_74FE3442A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, target_id INT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_794381C6F675F31B (author_id), INDEX IDX_794381C6158E0B66 (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE token_transaction (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, amount INT NOT NULL, type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, trajet_id INT DEFAULT NULL, INDEX IDX_5E06574BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajet (id INT AUTO_INCREMENT NOT NULL, conducteur_id INT DEFAULT NULL, ville_depart VARCHAR(100) NOT NULL, ville_arrivee VARCHAR(100) NOT NULL, date_depart DATETIME NOT NULL, date_arrivee DATETIME DEFAULT NULL, duree TIME DEFAULT NULL, places_disponibles INT NOT NULL, token_cost INT NOT NULL, type_vehicule VARCHAR(100) DEFAULT NULL, energie VARCHAR(50) DEFAULT NULL, est_ecologique TINYINT(1) NOT NULL, commentaire LONGTEXT DEFAULT NULL, conducteur_confirme_fin TINYINT(1) NOT NULL, INDEX IDX_2B5BA98CF16F4AC6 (conducteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajet_passager (id INT AUTO_INCREMENT NOT NULL, trajet_id INT NOT NULL, passager_id INT NOT NULL, is_paid TINYINT(1) NOT NULL, passager_confirme_fin TINYINT(1) NOT NULL, a_deja_note TINYINT(1) NOT NULL, INDEX IDX_E8EE2CCDD12A823 (trajet_id), INDEX IDX_E8EE2CCD71A51189 (passager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(50) NOT NULL, nom VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', photo VARCHAR(255) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, date_naissance DATE DEFAULT NULL, bio LONGTEXT DEFAULT NULL, aimlab_best_avg DOUBLE PRECISION DEFAULT NULL, musique VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, discussion VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, animaux VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, pauses_cafe VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, fumeur VARCHAR(15) DEFAULT \'indifferent\' NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, tokens INT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aimlab_score ADD CONSTRAINT FK_74FE3442A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6158E0B66 FOREIGN KEY (target_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE token_transaction ADD CONSTRAINT FK_5E06574BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT FK_2B5BA98CF16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE trajet_passager ADD CONSTRAINT FK_E8EE2CCDD12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)');
        $this->addSql('ALTER TABLE trajet_passager ADD CONSTRAINT FK_E8EE2CCD71A51189 FOREIGN KEY (passager_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aimlab_score DROP FOREIGN KEY FK_74FE3442A76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6F675F31B');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6158E0B66');
        $this->addSql('ALTER TABLE token_transaction DROP FOREIGN KEY FK_5E06574BA76ED395');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY FK_2B5BA98CF16F4AC6');
        $this->addSql('ALTER TABLE trajet_passager DROP FOREIGN KEY FK_E8EE2CCDD12A823');
        $this->addSql('ALTER TABLE trajet_passager DROP FOREIGN KEY FK_E8EE2CCD71A51189');
        $this->addSql('DROP TABLE aimlab_score');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE token_transaction');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('DROP TABLE trajet_passager');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
