<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151105142942 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Publication DROP FOREIGN KEY FK_29A0E8AE61B28F4');
        $this->addSql('ALTER TABLE Publication ADD CONSTRAINT FK_29A0E8AE61B28F4 FOREIGN KEY (publicationcolor_id) REFERENCES PublicationColor (id) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Publication DROP FOREIGN KEY FK_29A0E8AE61B28F4');
        $this->addSql('ALTER TABLE Publication ADD CONSTRAINT FK_29A0E8AE61B28F4 FOREIGN KEY (publicationcolor_id) REFERENCES PublicationColor (id)');
    }
}
