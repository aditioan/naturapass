<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160203165303 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE card_has_label_has_content DROP FOREIGN KEY FK_7040CFC133B92F39');
        $this->addSql('ALTER TABLE card_has_label_has_content ADD CONSTRAINT FK_7040CFC133B92F39 FOREIGN KEY (label_id) REFERENCES `card_has_label` (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `card_has_label_has_content` DROP FOREIGN KEY FK_7040CFC133B92F39');
        $this->addSql('ALTER TABLE `card_has_label_has_content` ADD CONSTRAINT FK_7040CFC133B92F39 FOREIGN KEY (label_id) REFERENCES card_has_label (id)');
    }
}
