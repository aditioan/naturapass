<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151210205206 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE observation_sharing_receiver (observation_id INT NOT NULL, observationreceiver_id INT NOT NULL, INDEX IDX_E1D3D4E31409DD88 (observation_id), INDEX IDX_E1D3D4E3FC68C9A3 (observationreceiver_id), PRIMARY KEY(observation_id, observationreceiver_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `receiver_has_observation_attachment` (label_id INT NOT NULL, observationreceiver_id INT NOT NULL, value LONGTEXT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_CB7D0F4933B92F39 (label_id), INDEX IDX_CB7D0F49FC68C9A3 (observationreceiver_id), PRIMARY KEY(label_id, observationreceiver_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD CONSTRAINT FK_E1D3D4E31409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD CONSTRAINT FK_E1D3D4E3FC68C9A3 FOREIGN KEY (observationreceiver_id) REFERENCES `receiver_has_observation` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` ADD CONSTRAINT FK_CB7D0F4933B92F39 FOREIGN KEY (label_id) REFERENCES `card_has_label` (id)');
        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` ADD CONSTRAINT FK_CB7D0F49FC68C9A3 FOREIGN KEY (observationreceiver_id) REFERENCES `receiver_has_observation` (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE observation_sharing_receiver');
        $this->addSql('DROP TABLE `receiver_has_observation_attachment`');
    }
}
