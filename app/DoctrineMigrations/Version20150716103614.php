<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150716103614 extends AbstractMigration {

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE parameters_has_notification ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE parameters_has_notification nhp SET nhp.type = (SELECT n.type FROM NotificationModel n WHERE n.id = nhp.notification_id)');
        $this->addSql('ALTER TABLE parameters_has_notification DROP FOREIGN KEY FK_A9B3ACDEEF1A9D84');
        //$this->addSql('DROP TABLE IF EXISTS NotificationModel');
        //$this->addSql('DROP TABLE IF EXISTS  observation_has_category_has_attachment');
        $this->addSql('DROP INDEX IDX_A9B3ACDEEF1A9D84 ON parameters_has_notification');
        $this->addSql('ALTER TABLE parameters_has_notification DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE parameters_has_notification DROP notification_id');
        $this->addSql('ALTER TABLE parameters_has_notification ADD PRIMARY KEY (parameters_id, `type`)');
        $this->addSql('ALTER TABLE Notification CHANGE content content VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        //$this->addSql('CREATE TABLE NotificationModel (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, route VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, multiple TINYINT(1) NOT NULL, site TINYINT(1) NOT NULL, device TINYINT(1) NOT NULL, push TINYINT(1) NOT NULL, `order` INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE observation_has_category_has_attachment (id INT AUTO_INCREMENT NOT NULL, observation_id INT NOT NULL, label_id INT NOT NULL, value LONGTEXT NOT NULL COLLATE utf8_unicode_ci, created DATE NOT NULL, updated DATE NOT NULL, UNIQUE INDEX UNIQ_5BA1CD1C33B92F39 (label_id), INDEX IDX_5BA1CD1C1409DD88 (observation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE observation_has_category_has_attachment ADD CONSTRAINT FK_5BA1CD1C1409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE observation_has_category_has_attachment ADD CONSTRAINT FK_5BA1CD1C33B92F39 FOREIGN KEY (label_id) REFERENCES card_has_label (id)');
        $this->addSql('ALTER TABLE Notification CHANGE content content LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE parameters_has_notification DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE parameters_has_notification ADD notification_id INT NOT NULL');
        $this->addSql('UPDATE parameters_has_notification phn SET phn.notification_id = (SELECT nm.id FROM NotificationModel WHERE nm.type LIKE phn.type)');
        $this->addSql('ALTER TABLE parameters_has_notification DROP type');
        $this->addSql('ALTER TABLE parameters_has_notification ADD CONSTRAINT FK_A9B3ACDEEF1A9D84 FOREIGN KEY (notification_id) REFERENCES NotificationModel (id)');
        $this->addSql('CREATE INDEX IDX_A9B3ACDEEF1A9D84 ON parameters_has_notification (notification_id)');
        $this->addSql('ALTER TABLE parameters_has_notification ADD PRIMARY KEY (parameters_id, notification_id)');
    }

}
