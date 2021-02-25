<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151117124732 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE locality DROP FOREIGN KEY FK_E1D6B8E69F2C3FAB');
        $this->addSql('ALTER TABLE locality ADD CONSTRAINT FK_E1D6B8E69F2C3FAB FOREIGN KEY (zone_id) REFERENCES `zone` (id) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `locality` DROP FOREIGN KEY FK_E1D6B8E69F2C3FAB');
        $this->addSql('ALTER TABLE `locality` ADD CONSTRAINT FK_E1D6B8E69F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
    }
}
