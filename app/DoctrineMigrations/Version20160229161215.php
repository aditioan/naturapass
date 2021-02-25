<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160229161215 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_has_hunt_location_type DROP FOREIGN KEY FK_E2D999FB8A3BABAC');
        $this->addSql('CREATE TABLE user_hunt_location_type (id INT AUTO_INCREMENT NOT NULL, parameter_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_4C0D72767C56DBD6 (parameter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_hunt_location_type ADD CONSTRAINT FK_4C0D72767C56DBD6 FOREIGN KEY (parameter_id) REFERENCES user_hunt_location (id)');
        $this->addSql('DROP TABLE hunt_location');
        $this->addSql('DROP TABLE user_has_hunt_location_type');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hunt_location (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_hunt_location_type (parameter_id INT NOT NULL, huntlocation_id INT NOT NULL, INDEX IDX_E2D999FB7C56DBD6 (parameter_id), INDEX IDX_E2D999FB8A3BABAC (huntlocation_id), PRIMARY KEY(parameter_id, huntlocation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_has_hunt_location_type ADD CONSTRAINT FK_E2D999FB8A3BABAC FOREIGN KEY (huntlocation_id) REFERENCES hunt_location (id)');
        $this->addSql('ALTER TABLE user_has_hunt_location_type ADD CONSTRAINT FK_E2D999FB7C56DBD6 FOREIGN KEY (parameter_id) REFERENCES user_hunt_location (id)');
        $this->addSql('DROP TABLE user_hunt_location_type');
    }
}
