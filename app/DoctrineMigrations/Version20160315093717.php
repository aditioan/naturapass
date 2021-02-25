<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160315093717 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM  user_device_sended_ids');
        $this->addSql('ALTER TABLE user_device_sended_ids DROP FOREIGN KEY FK_5CA9E8E94A4C7D4');
        $this->addSql('DROP INDEX IDX_5CA9E8E94A4C7D4 ON user_device_sended_ids');
        $this->addSql('ALTER TABLE user_device_sended_ids DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_device_sended_ids ADD guid VARCHAR(255) NOT NULL, DROP device_id');
        $this->addSql('ALTER TABLE user_device_sended_ids ADD PRIMARY KEY (user_id, guid, type)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_device_sended_ids DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_device_sended_ids ADD device_id INT NOT NULL, DROP guid');
        $this->addSql('ALTER TABLE user_device_sended_ids ADD CONSTRAINT FK_5CA9E8E94A4C7D4 FOREIGN KEY (device_id) REFERENCES Device (id)');
        $this->addSql('CREATE INDEX IDX_5CA9E8E94A4C7D4 ON user_device_sended_ids (device_id)');
        $this->addSql('ALTER TABLE user_device_sended_ids ADD PRIMARY KEY (user_id, device_id, type)');
    }
}
