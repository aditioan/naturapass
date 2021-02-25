<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150914122612 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE lounge_has_publication (publication_id INT NOT NULL, lounge_id INT NOT NULL, INDEX IDX_ED8D671F38B217A7 (publication_id), INDEX IDX_ED8D671F67D1F5E1 (lounge_id), PRIMARY KEY(publication_id, lounge_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lounge_has_publication ADD CONSTRAINT FK_ED8D671F38B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lounge_has_publication ADD CONSTRAINT FK_ED8D671F67D1F5E1 FOREIGN KEY (lounge_id) REFERENCES Lounge (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE lounge_has_publication');
    }
}
