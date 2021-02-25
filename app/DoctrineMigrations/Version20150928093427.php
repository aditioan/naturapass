<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150928093427 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE animal ADD media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231FEA9FDD75 FOREIGN KEY (media_id) REFERENCES animal_has_media (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6AAB231FEA9FDD75 ON animal (media_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `animal` DROP FOREIGN KEY FK_6AAB231FEA9FDD75');
        $this->addSql('DROP INDEX UNIQ_6AAB231FEA9FDD75 ON `animal`');
        $this->addSql('ALTER TABLE `animal` DROP media_id');
    }
}
