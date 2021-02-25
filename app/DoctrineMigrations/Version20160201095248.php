<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160201095248 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `favorite_has_attachment` (label_id INT NOT NULL, favorite_id INT NOT NULL, value LONGTEXT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_9376D97A33B92F39 (label_id), INDEX IDX_9376D97AAA17481D (favorite_id), PRIMARY KEY(label_id, favorite_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Favorite (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, publicationcolor_id INT DEFAULT NULL, category_id INT DEFAULT NULL, animal_id INT DEFAULT NULL, sharing_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, legend VARCHAR(255) DEFAULT NULL, specific_category INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_91B3EC8F7E3C61F9 (owner_id), INDEX IDX_91B3EC8F61B28F4 (publicationcolor_id), INDEX IDX_91B3EC8F12469DE2 (category_id), INDEX IDX_91B3EC8F8E962C16 (animal_id), UNIQUE INDEX UNIQ_91B3EC8F48F15050 (sharing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE group_has_favorite (favorite_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_95E53ED4AA17481D (favorite_id), INDEX IDX_95E53ED4FE54D947 (group_id), PRIMARY KEY(favorite_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lounge_has_favorite (favorite_id INT NOT NULL, lounge_id INT NOT NULL, INDEX IDX_34ED8DA4AA17481D (favorite_id), INDEX IDX_34ED8DA467D1F5E1 (lounge_id), PRIMARY KEY(favorite_id, lounge_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `favorite_has_attachment` ADD CONSTRAINT FK_9376D97A33B92F39 FOREIGN KEY (label_id) REFERENCES `card_has_label` (id)');
        $this->addSql('ALTER TABLE `favorite_has_attachment` ADD CONSTRAINT FK_9376D97AAA17481D FOREIGN KEY (favorite_id) REFERENCES Favorite (id)');
        $this->addSql('ALTER TABLE Favorite ADD CONSTRAINT FK_91B3EC8F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE Favorite ADD CONSTRAINT FK_91B3EC8F61B28F4 FOREIGN KEY (publicationcolor_id) REFERENCES PublicationColor (id)');
        $this->addSql('ALTER TABLE Favorite ADD CONSTRAINT FK_91B3EC8F12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE Favorite ADD CONSTRAINT FK_91B3EC8F8E962C16 FOREIGN KEY (animal_id) REFERENCES `animal` (id)');
        $this->addSql('ALTER TABLE Favorite ADD CONSTRAINT FK_91B3EC8F48F15050 FOREIGN KEY (sharing_id) REFERENCES Sharing (id)');
        $this->addSql('ALTER TABLE group_has_favorite ADD CONSTRAINT FK_95E53ED4AA17481D FOREIGN KEY (favorite_id) REFERENCES Favorite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_has_favorite ADD CONSTRAINT FK_95E53ED4FE54D947 FOREIGN KEY (group_id) REFERENCES `Group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lounge_has_favorite ADD CONSTRAINT FK_34ED8DA4AA17481D FOREIGN KEY (favorite_id) REFERENCES Favorite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lounge_has_favorite ADD CONSTRAINT FK_34ED8DA467D1F5E1 FOREIGN KEY (lounge_id) REFERENCES Lounge (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE favorite_has_attachment DROP FOREIGN KEY FK_9376D97AAA17481D');
        $this->addSql('ALTER TABLE group_has_favorite DROP FOREIGN KEY FK_95E53ED4AA17481D');
        $this->addSql('ALTER TABLE lounge_has_favorite DROP FOREIGN KEY FK_34ED8DA4AA17481D');
        $this->addSql('DROP TABLE `favorite_has_attachment`');
        $this->addSql('DROP TABLE Favorite');
        $this->addSql('DROP TABLE group_has_favorite');
        $this->addSql('DROP TABLE lounge_has_favorite');
    }
}
