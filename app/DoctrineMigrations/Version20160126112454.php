<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160126112454 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shape ADD ne_latitude VARCHAR(255) DEFAULT NULL, ADD ne_longitude VARCHAR(255) DEFAULT NULL, ADD sw_latitude VARCHAR(255) DEFAULT NULL, ADD sw_longitude VARCHAR(255) DEFAULT NULL, DROP nw_latitude, DROP nw_longitude, DROP se_latitude, DROP se_longitude');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `shape` ADD nw_latitude VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD nw_longitude VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD se_latitude VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD se_longitude VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, DROP ne_latitude, DROP ne_longitude, DROP sw_latitude, DROP sw_longitude');
    }
}
