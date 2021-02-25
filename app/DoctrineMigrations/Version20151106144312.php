<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151106144312 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `observation_sharing_receiver` (id INT AUTO_INCREMENT NOT NULL, receiver_id INT DEFAULT NULL, observation_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_E1D3D4E3CD53EDB6 (receiver_id), INDEX IDX_E1D3D4E31409DD88 (observation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `observation_sharing_receiver_attachment` (label_id INT NOT NULL, observationreceiver_id INT NOT NULL, value LONGTEXT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_90C99B5533B92F39 (label_id), INDEX IDX_90C99B55FC68C9A3 (observationreceiver_id), PRIMARY KEY(label_id, observationreceiver_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `observation_sharing_receiver` ADD CONSTRAINT FK_E1D3D4E3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id)');
        $this->addSql('ALTER TABLE `observation_sharing_receiver` ADD CONSTRAINT FK_E1D3D4E31409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE `observation_sharing_receiver_attachment` ADD CONSTRAINT FK_90C99B5533B92F39 FOREIGN KEY (label_id) REFERENCES `card_has_label` (id)');
        $this->addSql('ALTER TABLE `observation_sharing_receiver_attachment` ADD CONSTRAINT FK_90C99B55FC68C9A3 FOREIGN KEY (observationreceiver_id) REFERENCES `observation_sharing_receiver` (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE observation_sharing_receiver_attachment DROP FOREIGN KEY FK_90C99B55FC68C9A3');
        $this->addSql('DROP TABLE `observation_sharing_receiver`');
        $this->addSql('DROP TABLE `observation_sharing_receiver_attachment`');
    }
}
