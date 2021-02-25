<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160205132448 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE receiver_has_observation_attachment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` DROP id');
        $this->addSql('ALTER TABLE `receiver_has_observation_attachment` ADD PRIMARY KEY (label_id, observationreceiver_id)');
    }
}
