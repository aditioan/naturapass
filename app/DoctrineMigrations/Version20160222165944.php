<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160222165944 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE paper_model (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, type INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql("INSERT INTO `paper_model` (`id`, `name`, `type`, `created`, `updated`) VALUES (1, 'Permis de chasse', 3, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),(2, 'Validation permis', 3, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),(3, 'Police d''assurance', 3, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),(4, 'Carte euréenne d''arme à feu', 3, '0000-00-00 00:00:00', '0000-00-00 00:00:00');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE paper_model');
    }
}
