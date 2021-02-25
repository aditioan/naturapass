<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160125114907 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shape ADD nw_latitude VARCHAR(255) NOT NULL, ADD nw_longitude VARCHAR(255) NOT NULL, ADD ne_latitude VARCHAR(255) NOT NULL, ADD ne_longitude VARCHAR(255) NOT NULL, ADD sw_latitude VARCHAR(255) NOT NULL, ADD sw_longitude VARCHAR(255) NOT NULL, ADD se_latitude VARCHAR(255) NOT NULL, ADD se_longitude VARCHAR(255) NOT NULL');
//        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_5C4C4134CD53EDB6');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE `shape` DROP nw_latitude, DROP nw_longitude, DROP ne_latitude, DROP ne_longitude, DROP sw_latitude, DROP sw_longitude, DROP se_latitude, DROP se_longitude');
    }
}
