<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151210202538 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE receiver_has_observation_media (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `receiver_has_observation` (id INT AUTO_INCREMENT NOT NULL, receiver_id INT DEFAULT NULL, locality_id INT DEFAULT NULL, media_id INT DEFAULT NULL, category_id INT DEFAULT NULL, animal_id INT DEFAULT NULL, fullname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, latitude VARCHAR(255) NOT NULL, longitude VARCHAR(255) NOT NULL, altitude VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, content LONGTEXT NOT NULL, legend VARCHAR(255) DEFAULT NULL, specific_category INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_68DD67EBCD53EDB6 (receiver_id), INDEX IDX_68DD67EB88823A92 (locality_id), UNIQUE INDEX UNIQ_68DD67EBEA9FDD75 (media_id), INDEX IDX_68DD67EB12469DE2 (category_id), INDEX IDX_68DD67EB8E962C16 (animal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `receiver_has_observation` ADD CONSTRAINT FK_68DD67EBCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id)');
        $this->addSql('ALTER TABLE `receiver_has_observation` ADD CONSTRAINT FK_68DD67EB88823A92 FOREIGN KEY (locality_id) REFERENCES `locality` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `receiver_has_observation` ADD CONSTRAINT FK_68DD67EBEA9FDD75 FOREIGN KEY (media_id) REFERENCES receiver_has_observation_media (id)');
        $this->addSql('ALTER TABLE `receiver_has_observation` ADD CONSTRAINT FK_68DD67EB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `receiver_has_observation` ADD CONSTRAINT FK_68DD67EB8E962C16 FOREIGN KEY (animal_id) REFERENCES `animal` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE observation_sharing_receiver MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E3CD53EDB6');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E31409DD88');
        $this->addSql('DROP INDEX IDX_E1D3D4E3CD53EDB6 ON observation_sharing_receiver');
        $this->addSql('DROP INDEX IDX_E1D3D4E31409DD88 ON observation_sharing_receiver');
        $this->addSql('DROP TABLE observation_sharing_receiver_attachment');
        $this->addSql('DROP TABLE observation_sharing_receiver');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE receiver_has_observation DROP FOREIGN KEY FK_68DD67EBEA9FDD75');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E3FC68C9A3');
        $this->addSql('ALTER TABLE observation_sharing_receiver_attachment DROP FOREIGN KEY FK_90C99B55FC68C9A3');
        $this->addSql('DROP TABLE receiver_has_observation_media');
        $this->addSql('DROP TABLE `receiver_has_observation`');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E31409DD88');
        $this->addSql('DROP INDEX IDX_E1D3D4E3FC68C9A3 ON observation_sharing_receiver');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD id INT AUTO_INCREMENT NOT NULL, ADD receiver_id INT DEFAULT NULL, ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL, DROP observationreceiver_id, CHANGE observation_id observation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD CONSTRAINT FK_E1D3D4E3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES receiver (id)');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD CONSTRAINT FK_E1D3D4E31409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('CREATE INDEX IDX_E1D3D4E3CD53EDB6 ON observation_sharing_receiver (receiver_id)');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE `observation_sharing_receiver_attachment` DROP FOREIGN KEY FK_90C99B55FC68C9A3');
        $this->addSql('ALTER TABLE `observation_sharing_receiver_attachment` ADD CONSTRAINT FK_90C99B55FC68C9A3 FOREIGN KEY (observationreceiver_id) REFERENCES observation_sharing_receiver (id)');
    }
}
