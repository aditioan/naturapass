<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160201161442 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Favorite ADD card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Favorite ADD CONSTRAINT FK_91B3EC8F4ACC9A20 FOREIGN KEY (card_id) REFERENCES `card` (id)');
        $this->addSql('CREATE INDEX IDX_91B3EC8F4ACC9A20 ON Favorite (card_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Favorite DROP FOREIGN KEY FK_91B3EC8F4ACC9A20');
        $this->addSql('DROP INDEX IDX_91B3EC8F4ACC9A20 ON Favorite');
        $this->addSql('ALTER TABLE Favorite DROP card_id');
    }
}
