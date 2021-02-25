<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151002100242 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE receiver_has_user (receiver_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E86AF6E3CD53EDB6 (receiver_id), INDEX IDX_E86AF6E3A76ED395 (user_id), PRIMARY KEY(receiver_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE receiver_has_user ADD CONSTRAINT FK_E86AF6E3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE receiver_has_user ADD CONSTRAINT FK_E86AF6E3A76ED395 FOREIGN KEY (user_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE ReceiverUser');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ReceiverUser (receiver_id INT NOT NULL, user_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_31E096B2CD53EDB6 (receiver_id), INDEX IDX_31E096B2A76ED395 (user_id), PRIMARY KEY(receiver_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ReceiverUser ADD CONSTRAINT FK_31E096B2A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE ReceiverUser ADD CONSTRAINT FK_31E096B2CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES receiver (id)');
        $this->addSql('DROP TABLE receiver_has_user');
    }
}
