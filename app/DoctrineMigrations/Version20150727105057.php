<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150727105057 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE observation_sharing_recever DROP FOREIGN KEY FK_2760AB0754B75FF6');
        $this->addSql('ALTER TABLE recever_category_right DROP FOREIGN KEY FK_1934A77854B75FF6');
        $this->addSql('ALTER TABLE recever_localities DROP FOREIGN KEY FK_CDF706B154B75FF6');
        $this->addSql('ALTER TABLE recever DROP FOREIGN KEY FK_9A3511297E9E4C8C');
        $this->addSql('CREATE TABLE `observation_sharing_receiver` (receiver_id INT NOT NULL, observation_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_E1D3D4E3CD53EDB6 (receiver_id), INDEX IDX_E1D3D4E31409DD88 (observation_id), PRIMARY KEY(receiver_id, observation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `receiver` (id INT AUTO_INCREMENT NOT NULL, photo_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_3DB88C967E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE receiver_localities (locality_id INT NOT NULL, receiver_id INT NOT NULL, INDEX IDX_243862088823A92 (locality_id), INDEX IDX_2438620CD53EDB6 (receiver_id), PRIMARY KEY(locality_id, receiver_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `receiver_category_right` (receiver_id INT NOT NULL, category_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_768F8EA4CD53EDB6 (receiver_id), INDEX IDX_768F8EA412469DE2 (category_id), PRIMARY KEY(receiver_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE receiver_has_media (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `observation_sharing_receiver` ADD CONSTRAINT FK_E1D3D4E3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id)');
        $this->addSql('ALTER TABLE `observation_sharing_receiver` ADD CONSTRAINT FK_E1D3D4E31409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE `receiver` ADD CONSTRAINT FK_3DB88C967E9E4C8C FOREIGN KEY (photo_id) REFERENCES receiver_has_media (id)');
        $this->addSql('ALTER TABLE receiver_localities ADD CONSTRAINT FK_243862088823A92 FOREIGN KEY (locality_id) REFERENCES `locality` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE receiver_localities ADD CONSTRAINT FK_2438620CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `receiver_category_right` ADD CONSTRAINT FK_768F8EA4CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id)');
        $this->addSql('ALTER TABLE `receiver_category_right` ADD CONSTRAINT FK_768F8EA412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('DROP TABLE observation_sharing_recever');
        $this->addSql('DROP TABLE recever');
        $this->addSql('DROP TABLE recever_category_right');
        $this->addSql('DROP TABLE recever_has_media');
        $this->addSql('DROP TABLE recever_localities');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE observation_sharing_receiver DROP FOREIGN KEY FK_E1D3D4E3CD53EDB6');
        $this->addSql('ALTER TABLE receiver_localities DROP FOREIGN KEY FK_2438620CD53EDB6');
        $this->addSql('ALTER TABLE receiver_category_right DROP FOREIGN KEY FK_768F8EA4CD53EDB6');
        $this->addSql('ALTER TABLE receiver DROP FOREIGN KEY FK_3DB88C967E9E4C8C');
        $this->addSql('CREATE TABLE observation_sharing_recever (recever_id INT NOT NULL, observation_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_2760AB0754B75FF6 (recever_id), INDEX IDX_2760AB071409DD88 (observation_id), PRIMARY KEY(recever_id, observation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recever (id INT AUTO_INCREMENT NOT NULL, photo_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_9A3511297E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recever_category_right (recever_id INT NOT NULL, category_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_1934A77854B75FF6 (recever_id), INDEX IDX_1934A77812469DE2 (category_id), PRIMARY KEY(recever_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recever_has_media (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, exif LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recever_localities (locality_id INT NOT NULL, recever_id INT NOT NULL, INDEX IDX_CDF706B188823A92 (locality_id), INDEX IDX_CDF706B154B75FF6 (recever_id), PRIMARY KEY(locality_id, recever_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE observation_sharing_recever ADD CONSTRAINT FK_2760AB071409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE observation_sharing_recever ADD CONSTRAINT FK_2760AB0754B75FF6 FOREIGN KEY (recever_id) REFERENCES recever (id)');
        $this->addSql('ALTER TABLE recever ADD CONSTRAINT FK_9A3511297E9E4C8C FOREIGN KEY (photo_id) REFERENCES recever_has_media (id)');
        $this->addSql('ALTER TABLE recever_category_right ADD CONSTRAINT FK_1934A77812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE recever_category_right ADD CONSTRAINT FK_1934A77854B75FF6 FOREIGN KEY (recever_id) REFERENCES recever (id)');
        $this->addSql('ALTER TABLE recever_localities ADD CONSTRAINT FK_CDF706B154B75FF6 FOREIGN KEY (recever_id) REFERENCES recever (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recever_localities ADD CONSTRAINT FK_CDF706B188823A92 FOREIGN KEY (locality_id) REFERENCES locality (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE `observation_sharing_receiver`');
        $this->addSql('DROP TABLE `receiver`');
        $this->addSql('DROP TABLE receiver_localities');
        $this->addSql('DROP TABLE `receiver_category_right`');
        $this->addSql('DROP TABLE receiver_has_media');
    }
}
