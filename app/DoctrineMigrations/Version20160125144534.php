<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160125144534 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shape CHANGE nw_latitude nw_latitude VARCHAR(255) DEFAULT NULL, CHANGE nw_longitude nw_longitude VARCHAR(255) DEFAULT NULL, CHANGE ne_latitude ne_latitude VARCHAR(255) DEFAULT NULL, CHANGE ne_longitude ne_longitude VARCHAR(255) DEFAULT NULL, CHANGE sw_latitude sw_latitude VARCHAR(255) DEFAULT NULL, CHANGE sw_longitude sw_longitude VARCHAR(255) DEFAULT NULL, CHANGE se_latitude se_latitude VARCHAR(255) DEFAULT NULL, CHANGE se_longitude se_longitude VARCHAR(255) DEFAULT NULL');
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
        $this->addSql('ALTER TABLE `shape` CHANGE nw_latitude nw_latitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE nw_longitude nw_longitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE ne_latitude ne_latitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE ne_longitude ne_longitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE sw_latitude sw_latitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE sw_longitude sw_longitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE se_latitude se_latitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE se_longitude se_longitude VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
