<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160608151524 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `Group` ADD allow_add_chat INT DEFAULT 1 NOT NULL, ADD allow_show_chat INT DEFAULT 1 NOT NULL');
        $this->addSql('INSERT INTO `device_has_db_version` (`id`, `version`, `sqlite`, `created`, `updated`) VALUES (NULL, \'20160608155200\', \'ALTER TABLE `tb_group` ADD `c_allow_chat_show` INTEGER;ALTER TABLE `tb_group`ADD `c_allow_chat_add` INTEGER\', \'2016-06-08 15:52:00\', \'2016-06-08 15:52:00\');');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `Group` DROP allow_add_chat, DROP allow_show_chat');
    }
}
