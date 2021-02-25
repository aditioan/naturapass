<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150723125015 extends AbstractMigration {

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE NotificationModel');
        $this->addSql('ALTER TABLE parameters_has_notification CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_43EA543712469DE2');
        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_43EA543738B217A7');
        $this->addSql('DROP INDEX IDX_C576DBE038B217A7 ON Observation');
        $this->addSql('CREATE INDEX IDX_43EA543738B217A7 ON Observation (publication_id)');
        $this->addSql('DROP INDEX IDX_C576DBE012469DE2 ON Observation');
        $this->addSql('CREATE INDEX IDX_43EA543712469DE2 ON Observation (category_id)');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_C576DBE012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_43EA543738B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE observation_sharing_fdc DROP FOREIGN KEY FK_559755C7B34ED8A6');
        $this->addSql('ALTER TABLE observation_sharing_fdc DROP FOREIGN KEY FK_559755C71409DD88');
        $this->addSql('ALTER TABLE observation_sharing_fdc DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD CONSTRAINT FK_559755C7B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES `fdc` (id)');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD CONSTRAINT FK_559755C71409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD PRIMARY KEY (fdc_id, observation_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE NotificationModel (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, route VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, multiple TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, site TINYINT(1) NOT NULL, device TINYINT(1) NOT NULL, push TINYINT(1) NOT NULL, `order` INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_43EA543738B217A7');
        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_43EA543712469DE2');
        $this->addSql('DROP INDEX idx_43ea543738b217a7 ON Observation');
        $this->addSql('CREATE INDEX IDX_C576DBE038B217A7 ON Observation (publication_id)');
        $this->addSql('DROP INDEX idx_43ea543712469de2 ON Observation');
        $this->addSql('CREATE INDEX IDX_C576DBE012469DE2 ON Observation (category_id)');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_43EA543738B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_43EA543712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` DROP FOREIGN KEY FK_559755C7B34ED8A6');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` DROP FOREIGN KEY FK_559755C71409DD88');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` DROP created, DROP updated');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` ADD CONSTRAINT FK_559755C7B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES fdc (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` ADD CONSTRAINT FK_559755C71409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `observation_sharing_fdc` ADD PRIMARY KEY (observation_id, fdc_id)');
        $this->addSql('ALTER TABLE parameters_has_notification CHANGE type type VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE utf8_unicode_ci');
    }

}
