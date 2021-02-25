<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160217155505 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE animal DROP INDEX UNIQ_6AAB231FEA9FDD75, ADD INDEX IDX_6AAB231FEA9FDD75 (media_id)');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231FEA9FDD75');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231FEA9FDD75 FOREIGN KEY (media_id) REFERENCES animal_has_media (id) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `animal` DROP INDEX IDX_6AAB231FEA9FDD75, ADD UNIQUE INDEX UNIQ_6AAB231FEA9FDD75 (media_id)');
        $this->addSql('ALTER TABLE `animal` DROP FOREIGN KEY FK_6AAB231FEA9FDD75');
        $this->addSql('ALTER TABLE `animal` ADD CONSTRAINT FK_6AAB231FEA9FDD75 FOREIGN KEY (media_id) REFERENCES animal_has_media (id)');
    }
}
