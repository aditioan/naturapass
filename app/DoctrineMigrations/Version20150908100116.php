<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150908100116 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE group_has_message (id INT AUTO_INCREMENT NOT NULL, group_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created DATETIME NOT NULL, INDEX IDX_F4F44765FE54D947 (group_id), INDEX IDX_F4F447657E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE group_has_message ADD CONSTRAINT FK_F4F44765FE54D947 FOREIGN KEY (group_id) REFERENCES `Group` (id)');
        $this->addSql('ALTER TABLE group_has_message ADD CONSTRAINT FK_F4F447657E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE group_has_message');
    }
}
