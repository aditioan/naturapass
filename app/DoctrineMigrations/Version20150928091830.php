<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150928091830 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE categorymedia_tag');
        $this->addSql('ALTER TABLE category_has_media DROP FOREIGN KEY FK_56C563821C7B5678');
        $this->addSql('ALTER TABLE category_has_media DROP FOREIGN KEY FK_56C5638248F15050');
        $this->addSql('DROP INDEX UNIQ_56C563821C7B5678 ON category_has_media');
        $this->addSql('DROP INDEX UNIQ_56C5638248F15050 ON category_has_media');
        $this->addSql('ALTER TABLE category_has_media DROP geolocation_id, DROP sharing_id, DROP legend');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE categorymedia_tag (categorymedia_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D1620465590F7C76 (categorymedia_id), INDEX IDX_D1620465BAD26311 (tag_id), PRIMARY KEY(categorymedia_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categorymedia_tag ADD CONSTRAINT FK_D1620465590F7C76 FOREIGN KEY (categorymedia_id) REFERENCES category_has_media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorymedia_tag ADD CONSTRAINT FK_D1620465BAD26311 FOREIGN KEY (tag_id) REFERENCES Tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_has_media ADD geolocation_id INT DEFAULT NULL, ADD sharing_id INT DEFAULT NULL, ADD legend VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE category_has_media ADD CONSTRAINT FK_56C563821C7B5678 FOREIGN KEY (geolocation_id) REFERENCES Geolocation (id)');
        $this->addSql('ALTER TABLE category_has_media ADD CONSTRAINT FK_56C5638248F15050 FOREIGN KEY (sharing_id) REFERENCES Sharing (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_56C563821C7B5678 ON category_has_media (geolocation_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_56C5638248F15050 ON category_has_media (sharing_id)');
    }
}
