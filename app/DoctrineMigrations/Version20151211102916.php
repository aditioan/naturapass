<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151211102916 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE receiver_has_observation ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE receiver_has_observation ADD CONSTRAINT FK_68DD67EBA76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('CREATE INDEX IDX_68DD67EBA76ED395 ON receiver_has_observation (user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `receiver_has_observation` DROP FOREIGN KEY FK_68DD67EBA76ED395');
        $this->addSql('DROP INDEX IDX_68DD67EBA76ED395 ON `receiver_has_observation`');
        $this->addSql('ALTER TABLE `receiver_has_observation` DROP user_id');
    }
}
