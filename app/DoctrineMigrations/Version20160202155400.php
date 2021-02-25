<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160202155400 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `receiver_category_right` DROP FOREIGN KEY `FK_768F8EA412469DE2`; ALTER TABLE `receiver_category_right` ADD CONSTRAINT `FK_768F8EA412469DE2` FOREIGN KEY (`category_id`) REFERENCES `naturapass`.`category`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql('ALTER TABLE Favorite DROP FOREIGN KEY FK_91B3EC8F4ACC9A20');
//        $this->addSql('DROP INDEX IDX_91B3EC8F4ACC9A20 ON Favorite');
//        $this->addSql('ALTER TABLE Favorite DROP card_id');
    }
}
