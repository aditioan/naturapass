<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151222152351 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `shape` (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, sharing_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL, updated DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_DD30FFD87E3C61F9 (owner_id), UNIQUE INDEX UNIQ_DD30FFD848F15050 (sharing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `shape_has_point` (id INT AUTO_INCREMENT NOT NULL, shape_id INT DEFAULT NULL, latitude VARCHAR(255) NOT NULL, longitude VARCHAR(255) NOT NULL, INDEX IDX_4BE0B08950266CBB (shape_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `shape` ADD CONSTRAINT FK_DD30FFD87E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE `shape` ADD CONSTRAINT FK_DD30FFD848F15050 FOREIGN KEY (sharing_id) REFERENCES Sharing (id)');
        $this->addSql('ALTER TABLE `shape_has_point` ADD CONSTRAINT FK_4BE0B08950266CBB FOREIGN KEY (shape_id) REFERENCES `shape` (id)');
//        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_5C4C4134CD53EDB6');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE shape_has_point DROP FOREIGN KEY FK_4BE0B08950266CBB');
        $this->addSql('DROP TABLE `shape`');
        $this->addSql('DROP TABLE `shape_has_point`');
//        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id)');
    }
}
