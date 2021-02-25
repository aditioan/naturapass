<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150721091047 extends AbstractMigration {

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `session` ADD `sess_lifetime` INT NOT NULL');
        $this->addSql('RENAME TABLE `group` TO `Group`');
        $this->addSql('RENAME TABLE `observation` TO `Observation`');
        $this->addSql('CREATE TABLE IF NOT EXISTS `locality` (
            `id` int(11) NOT NULL,
              `zone_id` int(11) DEFAULT NULL,
              `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `created` datetime NOT NULL,
              `updated` datetime NOT NULL,
              `administrative_area_level_2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `administrative_area_level_1` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `country` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `postal_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $this->addSql('ALTER TABLE `locality`  ADD PRIMARY KEY (`id`), ADD KEY `IDX_E1D6B8E69F2C3FAB` (`zone_id`)');
        $this->addSql('ALTER TABLE `locality` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE `locality` ADD CONSTRAINT `FK_E1D6B8E69F2C3FAB` FOREIGN KEY (`zone_id`) REFERENCES `zone` (`id`)');
        $this->addSql('CREATE TABLE observation_sharing_fdc (observation_id INT NOT NULL, fdc_id INT NOT NULL, INDEX IDX_559755C71409DD88 (observation_id), INDEX IDX_559755C7B34ED8A6 (fdc_id), PRIMARY KEY(observation_id, fdc_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `fdc_without_category_right` (fdc_id INT NOT NULL, category_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_B07907ACB34ED8A6 (fdc_id), INDEX IDX_B07907AC12469DE2 (category_id), PRIMARY KEY(fdc_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fdc_localities (locality_id INT NOT NULL, fdc_id INT NOT NULL, INDEX IDX_7D09721588823A92 (locality_id), INDEX IDX_7D097215B34ED8A6 (fdc_id), PRIMARY KEY(locality_id, fdc_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD CONSTRAINT FK_559755C71409DD88 FOREIGN KEY (observation_id) REFERENCES `Observation` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD CONSTRAINT FK_559755C7B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES `fdc` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `fdc_without_category_right` ADD CONSTRAINT FK_B07907ACB34ED8A6 FOREIGN KEY (fdc_id) REFERENCES `fdc` (id)');
        $this->addSql('ALTER TABLE `fdc_without_category_right` ADD CONSTRAINT FK_B07907AC12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE fdc_localities ADD CONSTRAINT FK_7D09721588823A92 FOREIGN KEY (locality_id) REFERENCES `locality` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fdc_localities ADD CONSTRAINT FK_7D097215B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES `fdc` (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE IF EXISTS observation_has_category_has_attachment');
        $this->addSql('DROP TABLE IF EXISTS zone_without_category_right');
        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_C576DBE012469DE2');
//        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_43EA543738B217A7');
        $this->addSql('DROP INDEX IDX_C576DBE012469DE2 ON Observation');
        $this->addSql('ALTER TABLE `Observation` ADD `publication_id` INT NULL DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_C576DBE038B217A7 ON Observation (publication_id)');
        $this->addSql('CREATE INDEX IDX_C576DBE012469DE2 ON Observation (category_id)');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_43EA543712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_43EA543738B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE category ADD card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C14ACC9A20 FOREIGN KEY (card_id) REFERENCES `card` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_64C19C14ACC9A20 ON category (card_id)');
        $this->addSql('ALTER TABLE fdc DROP FOREIGN KEY FK_6631F0C19F2C3FAB');
        $this->addSql('DROP INDEX IDX_6631F0C19F2C3FAB ON fdc');
        $this->addSql('ALTER TABLE fdc DROP zone_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('RENAME TABLE `observation` TO `observation`');
        $this->addSql('CREATE TABLE observation_has_category_has_attachment (id INT AUTO_INCREMENT NOT NULL, observation_id INT NOT NULL, label_id INT NOT NULL, value LONGTEXT NOT NULL COLLATE utf8_unicode_ci, created DATE NOT NULL, updated DATE NOT NULL, UNIQUE INDEX UNIQ_5BA1CD1C33B92F39 (label_id), INDEX IDX_5BA1CD1C1409DD88 (observation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone_without_category_right (zone_id INT NOT NULL, category_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_E42F315E9F2C3FAB (zone_id), INDEX IDX_E42F315E12469DE2 (category_id), PRIMARY KEY(zone_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE observation_has_category_has_attachment ADD CONSTRAINT FK_5BA1CD1C1409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE observation_has_category_has_attachment ADD CONSTRAINT FK_5BA1CD1C33B92F39 FOREIGN KEY (label_id) REFERENCES card_has_label (id)');
        $this->addSql('ALTER TABLE zone_without_category_right ADD CONSTRAINT FK_E42F315E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE zone_without_category_right ADD CONSTRAINT FK_E42F315E9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('DROP TABLE observation_sharing_fdc');
        $this->addSql('DROP TABLE `fdc_without_category_right`');
        $this->addSql('DROP TABLE fdc_localities');
        $this->addSql('ALTER TABLE `locality` DROP FOREIGN KEY FK_E1D6B8E69F2C3FAB');
        $this->addSql('DROP INDEX IDX_E1D6B8E69F2C3FAB ON `locality`');
        $this->addSql('ALTER TABLE `locality` CHANGE zone_id fdc_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `locality` ADD CONSTRAINT FK_4CE6C7A4B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES fdc (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_18A0DAB0B34ED8A6 ON `locality` (fdc_id)');
        $this->addSql('ALTER TABLE `Observation` DROP FOREIGN KEY FK_C576DBE038B217A7');
        $this->addSql('ALTER TABLE `Observation` DROP FOREIGN KEY FK_C576DBE012469DE2');
        $this->addSql('DROP INDEX idx_c576dbe038b217a7 ON `Observation`');
        $this->addSql('CREATE INDEX IDX_43EA543738B217A7 ON `Observation` (publication_id)');
        $this->addSql('DROP INDEX idx_c576dbe012469de2 ON `Observation`');
        $this->addSql('CREATE INDEX IDX_43EA543712469DE2 ON `Observation` (category_id)');
        $this->addSql('ALTER TABLE `Observation` ADD CONSTRAINT FK_C576DBE038B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE `Observation` ADD CONSTRAINT FK_C576DBE012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C14ACC9A20');
        $this->addSql('DROP INDEX IDX_64C19C14ACC9A20 ON category');
        $this->addSql('ALTER TABLE category DROP card_id');
        $this->addSql('ALTER TABLE `fdc` ADD zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `fdc` ADD CONSTRAINT FK_6631F0C19F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('CREATE INDEX IDX_6631F0C19F2C3FAB ON `fdc` (zone_id)');
    }

}
