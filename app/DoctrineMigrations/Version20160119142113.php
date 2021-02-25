<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160119142113 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE group_has_shape (shape_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_E60AB38750266CBB (shape_id), INDEX IDX_E60AB387FE54D947 (group_id), PRIMARY KEY(shape_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lounge_has_shape (shape_id INT NOT NULL, lounge_id INT NOT NULL, INDEX IDX_B51015250266CBB (shape_id), INDEX IDX_B51015267D1F5E1 (lounge_id), PRIMARY KEY(shape_id, lounge_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE group_has_shape ADD CONSTRAINT FK_E60AB38750266CBB FOREIGN KEY (shape_id) REFERENCES `shape` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_has_shape ADD CONSTRAINT FK_E60AB387FE54D947 FOREIGN KEY (group_id) REFERENCES `Group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lounge_has_shape ADD CONSTRAINT FK_B51015250266CBB FOREIGN KEY (shape_id) REFERENCES `shape` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lounge_has_shape ADD CONSTRAINT FK_B51015267D1F5E1 FOREIGN KEY (lounge_id) REFERENCES Lounge (id) ON DELETE CASCADE');
//        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_5C4C4134CD53EDB6');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE group_has_shape');
        $this->addSql('DROP TABLE lounge_has_shape');
//        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id)');
    }
}
