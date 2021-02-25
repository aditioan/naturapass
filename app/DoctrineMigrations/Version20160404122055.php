<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160404122055 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE parameters_has_notification CHANGE wanted wanted INT NOT NULL');
        $this->addSql('ALTER TABLE parameters_has_email CHANGE wanted wanted INT NOT NULL');
        $this->addSql('ALTER TABLE paper_model ADD title VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE paper_model DROP title');
        $this->addSql('ALTER TABLE parameters_has_email CHANGE wanted wanted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE parameters_has_notification CHANGE wanted wanted TINYINT(1) NOT NULL');
    }
}
