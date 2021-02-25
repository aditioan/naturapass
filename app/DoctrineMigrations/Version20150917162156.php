<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150917162156 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE admin_news ADD title VARCHAR(255) NOT NULL, ADD content LONGTEXT NOT NULL, ADD photo VARCHAR(255) NOT NULL, DROP created, DROP updated, DROP fr, DROP en, DROP de, DROP es');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE admin_news ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL, ADD en LONGTEXT NOT NULL COLLATE utf8_unicode_ci, ADD de LONGTEXT NOT NULL COLLATE utf8_unicode_ci, ADD es LONGTEXT NOT NULL COLLATE utf8_unicode_ci, DROP title, DROP photo, CHANGE content fr LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
    }
}
