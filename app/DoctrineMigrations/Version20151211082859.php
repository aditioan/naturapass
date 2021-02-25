<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151211082859 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E3FC68C9A3');
        $this->addSql('DROP INDEX IDX_E1D3D4E3FC68C9A3 ON observation_sharing_receiver');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE observation_sharing_receiver CHANGE observationreceiver_id receiver_id INT NOT NULL');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD CONSTRAINT FK_E1D3D4E3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E1D3D4E3CD53EDB6 ON observation_sharing_receiver (receiver_id)');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD PRIMARY KEY (observation_id, receiver_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E3CD53EDB6');
        $this->addSql('DROP INDEX IDX_E1D3D4E3CD53EDB6 ON observation_sharing_receiver');
        $this->addSql('ALTER TABLE observation_sharing_receiver DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE observation_sharing_receiver CHANGE receiver_id observationreceiver_id INT NOT NULL');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD CONSTRAINT FK_E1D3D4E3FC68C9A3 FOREIGN KEY (observationreceiver_id) REFERENCES receiver_has_observation (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E1D3D4E3FC68C9A3 ON observation_sharing_receiver (observationreceiver_id)');
        $this->addSql('ALTER TABLE observation_sharing_receiver ADD PRIMARY KEY (observation_id, observationreceiver_id)');
    }
}
