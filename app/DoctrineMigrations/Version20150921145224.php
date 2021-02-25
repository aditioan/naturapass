<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150921145224 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE group_has_lounge (lounge_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_27B5260467D1F5E1 (lounge_id), INDEX IDX_27B52604FE54D947 (group_id), PRIMARY KEY(lounge_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE group_has_lounge ADD CONSTRAINT FK_27B5260467D1F5E1 FOREIGN KEY (lounge_id) REFERENCES Lounge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_has_lounge ADD CONSTRAINT FK_27B52604FE54D947 FOREIGN KEY (group_id) REFERENCES `Group` (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE group_has_lounge');
    }
}
