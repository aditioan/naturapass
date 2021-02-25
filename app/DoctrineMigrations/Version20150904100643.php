<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150904100643 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE category_has_media (id INT AUTO_INCREMENT NOT NULL, geolocation_id INT DEFAULT NULL, sharing_id INT DEFAULT NULL, legend VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_56C563821C7B5678 (geolocation_id), UNIQUE INDEX UNIQ_56C5638248F15050 (sharing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categorymedia_tag (categorymedia_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D1620465590F7C76 (categorymedia_id), INDEX IDX_D1620465BAD26311 (tag_id), PRIMARY KEY(categorymedia_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category_has_media ADD CONSTRAINT FK_56C563821C7B5678 FOREIGN KEY (geolocation_id) REFERENCES Geolocation (id)');
        $this->addSql('ALTER TABLE category_has_media ADD CONSTRAINT FK_56C5638248F15050 FOREIGN KEY (sharing_id) REFERENCES Sharing (id)');
        $this->addSql('ALTER TABLE categorymedia_tag ADD CONSTRAINT FK_D1620465590F7C76 FOREIGN KEY (categorymedia_id) REFERENCES category_has_media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorymedia_tag ADD CONSTRAINT FK_D1620465BAD26311 FOREIGN KEY (tag_id) REFERENCES Tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1EA9FDD75 FOREIGN KEY (media_id) REFERENCES category_has_media (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1EA9FDD75 ON category (media_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE categorymedia_tag DROP FOREIGN KEY FK_D1620465590F7C76');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1EA9FDD75');
        $this->addSql('DROP TABLE category_has_media');
        $this->addSql('DROP TABLE categorymedia_tag');
        $this->addSql('DROP INDEX UNIQ_64C19C1EA9FDD75 ON category');
        $this->addSql('ALTER TABLE category DROP media_id');
    }
}
