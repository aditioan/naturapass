<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151105121223 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Publication DROP INDEX UNIQ_29A0E8AE61B28F4, ADD INDEX IDX_29A0E8AE61B28F4 (publicationcolor_id)');
        $this->addSql('ALTER TABLE locality CHANGE administrative_area_level_2 administrative_area_level_2 VARCHAR(255) DEFAULT NULL, CHANGE administrative_area_level_1 administrative_area_level_1 VARCHAR(255) DEFAULT NULL, CHANGE country country VARCHAR(255) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Publication DROP INDEX IDX_29A0E8AE61B28F4, ADD UNIQUE INDEX UNIQ_29A0E8AE61B28F4 (publicationcolor_id)');
        $this->addSql('ALTER TABLE `locality` CHANGE administrative_area_level_2 administrative_area_level_2 VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE administrative_area_level_1 administrative_area_level_1 VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE country country VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE postal_code postal_code VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
