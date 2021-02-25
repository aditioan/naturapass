<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150918152508 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE admin_news ADD photo_id INT DEFAULT NULL, DROP photo');
        $this->addSql('ALTER TABLE admin_news ADD CONSTRAINT FK_3DCA1BB07E9E4C8C FOREIGN KEY (photo_id) REFERENCES admin_news_has_media (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3DCA1BB07E9E4C8C ON admin_news (photo_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE admin_news DROP FOREIGN KEY FK_3DCA1BB07E9E4C8C');
        $this->addSql('DROP INDEX UNIQ_3DCA1BB07E9E4C8C ON admin_news');
        $this->addSql('ALTER TABLE admin_news ADD photo VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, DROP photo_id');
    }
}
