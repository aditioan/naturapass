<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160224170237 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hunt_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_hunt (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, type INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_9C2B66187E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_hunt_type (parameter_id INT NOT NULL, hunttype_id INT NOT NULL, INDEX IDX_76CA3FB97C56DBD6 (parameter_id), INDEX IDX_76CA3FB9756B1EFC (hunttype_id), PRIMARY KEY(parameter_id, hunttype_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_has_hunt ADD CONSTRAINT FK_9C2B66187E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_has_hunt_type ADD CONSTRAINT FK_76CA3FB97C56DBD6 FOREIGN KEY (parameter_id) REFERENCES user_has_hunt (id)');
        $this->addSql('ALTER TABLE user_has_hunt_type ADD CONSTRAINT FK_76CA3FB9756B1EFC FOREIGN KEY (hunttype_id) REFERENCES hunt_type (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_has_hunt_type DROP FOREIGN KEY FK_76CA3FB9756B1EFC');
        $this->addSql('ALTER TABLE user_has_hunt_type DROP FOREIGN KEY FK_76CA3FB97C56DBD6');
        $this->addSql('DROP TABLE hunt_type');
        $this->addSql('DROP TABLE user_has_hunt');
        $this->addSql('DROP TABLE user_has_hunt_type');
    }
}
