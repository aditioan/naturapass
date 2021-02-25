<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150930155241 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE receiver_has_group (receiver_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_5BA6F713CD53EDB6 (receiver_id), INDEX IDX_5BA6F713FE54D947 (group_id), PRIMARY KEY(receiver_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE receiver_has_group ADD CONSTRAINT FK_5BA6F713CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE receiver_has_group ADD CONSTRAINT FK_5BA6F713FE54D947 FOREIGN KEY (group_id) REFERENCES `Group` (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE receiver_has_group');
    }
}
