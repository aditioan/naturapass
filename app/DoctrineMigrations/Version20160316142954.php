<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160316142954 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_hunt_country (owner_id INT NOT NULL, country_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_7A4A0F427E3C61F9 (owner_id), INDEX IDX_7A4A0F42F92F3E70 (country_id), PRIMARY KEY(owner_id, country_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_hunt_city (owner_id INT NOT NULL, city_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_1884D85F7E3C61F9 (owner_id), INDEX IDX_1884D85F8BAC62AF (city_id), PRIMARY KEY(owner_id, city_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Country (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_hunt_country ADD CONSTRAINT FK_7A4A0F427E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_hunt_country ADD CONSTRAINT FK_7A4A0F42F92F3E70 FOREIGN KEY (country_id) REFERENCES Country (id)');
        $this->addSql('ALTER TABLE user_hunt_city ADD CONSTRAINT FK_1884D85F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_hunt_city ADD CONSTRAINT FK_1884D85F8BAC62AF FOREIGN KEY (city_id) REFERENCES `locality` (id)');
        $this->addSql("INSERT INTO `Country` (`id`, `name`) VALUES (1, 'France'),(2, 'Espagne'),(3, 'Allemagne'),(4, 'Belgique'),(5, 'Italie'),(6, 'Finlande');");
        $this->addSql('DROP TABLE user_hunt_location');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_hunt_country DROP FOREIGN KEY FK_7A4A0F42F92F3E70');
        $this->addSql('CREATE TABLE user_hunt_location (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, type INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, INDEX IDX_62B454DC7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_hunt_location ADD CONSTRAINT FK_62B454DC7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('DROP TABLE user_hunt_country');
        $this->addSql('DROP TABLE user_hunt_city');
        $this->addSql('DROP TABLE Country');
    }
}
