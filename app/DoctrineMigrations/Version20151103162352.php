<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151103162352 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE PublicationColor (id INT AUTO_INCREMENT NOT NULL, color VARCHAR(25) NOT NULL, name VARCHAR(256) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Publication ADD publicationcolor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Publication ADD CONSTRAINT FK_29A0E8AE61B28F4 FOREIGN KEY (publicationcolor_id) REFERENCES PublicationColor (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_29A0E8AE61B28F4 ON Publication (publicationcolor_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Publication DROP FOREIGN KEY FK_29A0E8AE61B28F4');
        $this->addSql('DROP TABLE PublicationColor');
        $this->addSql('DROP INDEX UNIQ_29A0E8AE61B28F4 ON Publication');
        $this->addSql('ALTER TABLE Publication DROP publicationcolor_id');
    }
}
