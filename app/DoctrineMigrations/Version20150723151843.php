<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150723151843 extends AbstractMigration {

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fdc_localities DROP FOREIGN KEY FK_7D097215B34ED8A6');
        $this->addSql('ALTER TABLE fdc_without_category_right DROP FOREIGN KEY FK_B07907ACB34ED8A6');
        $this->addSql('ALTER TABLE observation_sharing_fdc DROP FOREIGN KEY FK_559755C7B34ED8A6');
        $this->addSql('ALTER TABLE fdc DROP FOREIGN KEY FK_6631F0C17E9E4C8C');
        $this->addSql('CREATE TABLE `observation_sharing_recever` (recever_id INT NOT NULL, observation_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_2760AB0754B75FF6 (recever_id), INDEX IDX_2760AB071409DD88 (observation_id), PRIMARY KEY(recever_id, observation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recever_localities (locality_id INT NOT NULL, recever_id INT NOT NULL, INDEX IDX_CDF706B188823A92 (locality_id), INDEX IDX_CDF706B154B75FF6 (recever_id), PRIMARY KEY(locality_id, recever_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `recever_category_right` (recever_id INT NOT NULL, category_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_1934A77854B75FF6 (recever_id), INDEX IDX_1934A77812469DE2 (category_id), PRIMARY KEY(recever_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recever_has_media (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `recever` (id INT AUTO_INCREMENT NOT NULL, photo_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_9A3511297E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `observation_sharing_recever` ADD CONSTRAINT FK_2760AB0754B75FF6 FOREIGN KEY (recever_id) REFERENCES `recever` (id)');
        $this->addSql('ALTER TABLE `observation_sharing_recever` ADD CONSTRAINT FK_2760AB071409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE recever_localities ADD CONSTRAINT FK_CDF706B188823A92 FOREIGN KEY (locality_id) REFERENCES `locality` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recever_localities ADD CONSTRAINT FK_CDF706B154B75FF6 FOREIGN KEY (recever_id) REFERENCES `recever` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `recever_category_right` ADD CONSTRAINT FK_1934A77854B75FF6 FOREIGN KEY (recever_id) REFERENCES `recever` (id)');
        $this->addSql('ALTER TABLE `recever_category_right` ADD CONSTRAINT FK_1934A77812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE `recever` ADD CONSTRAINT FK_9A3511297E9E4C8C FOREIGN KEY (photo_id) REFERENCES recever_has_media (id)');
        $this->addSql('ALTER TABLE town DROP FOREIGN KEY FK_4CE6C7A4B34ED8A6');
        $this->addSql('DROP INDEX IDX_4CE6C7A4B34ED8A6 ON town');
        $this->addSql('DROP TABLE town');
        $this->addSql('DROP TABLE fdc');
        $this->addSql('DROP TABLE fdc_has_media');
        $this->addSql('DROP TABLE fdc_localities');
        $this->addSql('DROP TABLE fdc_without_category_right');
        $this->addSql('DROP TABLE observation_sharing_fdc');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE recever DROP FOREIGN KEY FK_9A3511297E9E4C8C');
        $this->addSql('ALTER TABLE observation_sharing_recever DROP FOREIGN KEY FK_2760AB0754B75FF6');
        $this->addSql('ALTER TABLE recever_localities DROP FOREIGN KEY FK_CDF706B154B75FF6');
        $this->addSql('ALTER TABLE recever_category_right DROP FOREIGN KEY FK_1934A77854B75FF6');
        $this->addSql('CREATE TABLE fdc (id INT AUTO_INCREMENT NOT NULL, photo_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_6631F0C17E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fdc_has_media (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, exif LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fdc_localities (locality_id INT NOT NULL, fdc_id INT NOT NULL, INDEX IDX_7D09721588823A92 (locality_id), INDEX IDX_7D097215B34ED8A6 (fdc_id), PRIMARY KEY(locality_id, fdc_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fdc_without_category_right (fdc_id INT NOT NULL, category_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_B07907ACB34ED8A6 (fdc_id), INDEX IDX_B07907AC12469DE2 (category_id), PRIMARY KEY(fdc_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE observation_sharing_fdc (fdc_id INT NOT NULL, observation_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_559755C71409DD88 (observation_id), INDEX IDX_559755C7B34ED8A6 (fdc_id), PRIMARY KEY(fdc_id, observation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fdc ADD CONSTRAINT FK_6631F0C17E9E4C8C FOREIGN KEY (photo_id) REFERENCES fdc_has_media (id)');
        $this->addSql('ALTER TABLE fdc_localities ADD CONSTRAINT FK_7D097215B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES fdc (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fdc_localities ADD CONSTRAINT FK_7D09721588823A92 FOREIGN KEY (locality_id) REFERENCES locality (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fdc_without_category_right ADD CONSTRAINT FK_B07907AC12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE fdc_without_category_right ADD CONSTRAINT FK_B07907ACB34ED8A6 FOREIGN KEY (fdc_id) REFERENCES fdc (id)');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD CONSTRAINT FK_559755C71409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE observation_sharing_fdc ADD CONSTRAINT FK_559755C7B34ED8A6 FOREIGN KEY (fdc_id) REFERENCES fdc (id)');
        $this->addSql('DROP TABLE `observation_sharing_recever`');
        $this->addSql('DROP TABLE recever_localities');
        $this->addSql('DROP TABLE `recever_category_right`');
        $this->addSql('DROP TABLE recever_has_media');
        $this->addSql('DROP TABLE `recever`');
    }

}
