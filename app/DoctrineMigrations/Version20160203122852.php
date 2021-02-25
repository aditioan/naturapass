<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160203122852 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_5C4C4134CD53EDB6');
        $this->addSql('ALTER TABLE card_category_by_zone DROP FOREIGN KEY FK_C23B014612469DE2');
        $this->addSql('ALTER TABLE card_category_by_zone ADD CONSTRAINT FK_C23B014612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
//        $this->addSql('ALTER TABLE receiver_category_right ADD CONSTRAINT FK_768F8EA412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql('ALTER TABLE `card_category_by_zone` DROP FOREIGN KEY FK_C23B014612469DE2');
        $this->addSql('ALTER TABLE `card_category_by_zone` ADD CONSTRAINT FK_C23B014612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id)');
//        $this->addSql('ALTER TABLE `receiver_category_right` DROP FOREIGN KEY FK_768F8EA412469DE2');
    }
}
