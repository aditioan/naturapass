<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160222132007 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE dog_has_photo (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dog_breed (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weapon_has_photo (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weapon_has_media (id INT AUTO_INCREMENT NOT NULL, weapon_id INT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_736CDF1495B82273 (weapon_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dog_has_media (id INT AUTO_INCREMENT NOT NULL, dog_id INT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_DDA4FE51634DFEB (dog_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_weapon (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, calibre_id INT DEFAULT NULL, brand_id INT DEFAULT NULL, photo_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, type INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_E27676917E3C61F9 (owner_id), INDEX IDX_E276769158FEF8CD (calibre_id), INDEX IDX_E276769144F5D008 (brand_id), UNIQUE INDEX UNIQ_E27676917E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_dog (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, breed_id INT DEFAULT NULL, type_id INT DEFAULT NULL, photo_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, sex INT NOT NULL, birthday DATETIME DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_EDE17A8D7E3C61F9 (owner_id), INDEX IDX_EDE17A8DA8B4A30F (breed_id), INDEX IDX_EDE17A8DC54C8C93 (type_id), UNIQUE INDEX UNIQ_EDE17A8D7E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dog_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weapon_calibre (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weapon_brand (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE weapon_has_media ADD CONSTRAINT FK_736CDF1495B82273 FOREIGN KEY (weapon_id) REFERENCES user_has_weapon (id)');
        $this->addSql('ALTER TABLE dog_has_media ADD CONSTRAINT FK_DDA4FE51634DFEB FOREIGN KEY (dog_id) REFERENCES user_has_dog (id)');
        $this->addSql('ALTER TABLE user_has_weapon ADD CONSTRAINT FK_E27676917E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_has_weapon ADD CONSTRAINT FK_E276769158FEF8CD FOREIGN KEY (calibre_id) REFERENCES weapon_calibre (id)');
        $this->addSql('ALTER TABLE user_has_weapon ADD CONSTRAINT FK_E276769144F5D008 FOREIGN KEY (brand_id) REFERENCES weapon_brand (id)');
        $this->addSql('ALTER TABLE user_has_weapon ADD CONSTRAINT FK_E27676917E9E4C8C FOREIGN KEY (photo_id) REFERENCES weapon_has_photo (id)');
        $this->addSql('ALTER TABLE user_has_dog ADD CONSTRAINT FK_EDE17A8D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_has_dog ADD CONSTRAINT FK_EDE17A8DA8B4A30F FOREIGN KEY (breed_id) REFERENCES dog_breed (id)');
        $this->addSql('ALTER TABLE user_has_dog ADD CONSTRAINT FK_EDE17A8DC54C8C93 FOREIGN KEY (type_id) REFERENCES dog_type (id)');
        $this->addSql('ALTER TABLE user_has_dog ADD CONSTRAINT FK_EDE17A8D7E9E4C8C FOREIGN KEY (photo_id) REFERENCES dog_has_photo (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_has_dog DROP FOREIGN KEY FK_EDE17A8D7E9E4C8C');
        $this->addSql('ALTER TABLE user_has_dog DROP FOREIGN KEY FK_EDE17A8DA8B4A30F');
        $this->addSql('ALTER TABLE user_has_weapon DROP FOREIGN KEY FK_E27676917E9E4C8C');
        $this->addSql('ALTER TABLE weapon_has_media DROP FOREIGN KEY FK_736CDF1495B82273');
        $this->addSql('ALTER TABLE dog_has_media DROP FOREIGN KEY FK_DDA4FE51634DFEB');
        $this->addSql('ALTER TABLE user_has_dog DROP FOREIGN KEY FK_EDE17A8DC54C8C93');
        $this->addSql('ALTER TABLE user_has_weapon DROP FOREIGN KEY FK_E276769158FEF8CD');
        $this->addSql('ALTER TABLE user_has_weapon DROP FOREIGN KEY FK_E276769144F5D008');
        $this->addSql('DROP TABLE dog_has_photo');
        $this->addSql('DROP TABLE dog_breed');
        $this->addSql('DROP TABLE weapon_has_photo');
        $this->addSql('DROP TABLE weapon_has_media');
        $this->addSql('DROP TABLE dog_has_media');
        $this->addSql('DROP TABLE user_has_weapon');
        $this->addSql('DROP TABLE user_has_dog');
        $this->addSql('DROP TABLE dog_type');
        $this->addSql('DROP TABLE weapon_calibre');
        $this->addSql('DROP TABLE weapon_brand');
    }
}
