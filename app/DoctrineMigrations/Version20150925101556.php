<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150925101556 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Observation ADD animal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Observation ADD CONSTRAINT FK_43EA54378E962C16 FOREIGN KEY (animal_id) REFERENCES `animal` (id)');
        $this->addSql('CREATE INDEX IDX_43EA54378E962C16 ON Observation (animal_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Observation DROP FOREIGN KEY FK_43EA54378E962C16');
        $this->addSql('DROP INDEX IDX_43EA54378E962C16 ON Observation');
        $this->addSql('ALTER TABLE Observation DROP animal_id');
    }
}
