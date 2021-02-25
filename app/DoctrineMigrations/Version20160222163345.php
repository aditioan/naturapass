<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160222163345 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_has_paper (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, text LONGTEXT DEFAULT NULL, type INT NOT NULL, deletable INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_B573FD4C7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paper_has_media (id INT AUTO_INCREMENT NOT NULL, paper_id INT NOT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, state INT NOT NULL, path VARCHAR(255) NOT NULL, exif LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_53A54363E6758861 (paper_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_has_paper ADD CONSTRAINT FK_B573FD4C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE paper_has_media ADD CONSTRAINT FK_53A54363E6758861 FOREIGN KEY (paper_id) REFERENCES user_has_paper (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE paper_has_media DROP FOREIGN KEY FK_53A54363E6758861');
        $this->addSql('DROP TABLE user_has_paper');
        $this->addSql('DROP TABLE paper_has_media');
    }
}
