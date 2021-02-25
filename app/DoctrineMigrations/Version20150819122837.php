<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150819122837 extends AbstractMigration {

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ReceiverUser (receiver_id INT NOT NULL, user_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_31E096B2CD53EDB6 (receiver_id), INDEX IDX_31E096B2A76ED395 (user_id), PRIMARY KEY(receiver_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ReceiverUser ADD CONSTRAINT FK_31E096B2CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `receiver` (id)');
        $this->addSql('ALTER TABLE ReceiverUser ADD CONSTRAINT FK_31E096B2A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');

        $this->addSql('CREATE TABLE `observation_has_attachment` (label_id INT NOT NULL, observation_id INT NOT NULL, value LONGTEXT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_768BDB3433B92F39 (label_id), INDEX IDX_768BDB341409DD88 (observation_id), PRIMARY KEY(label_id, observation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `observation_has_attachment` ADD CONSTRAINT FK_768BDB3433B92F39 FOREIGN KEY (label_id) REFERENCES `card_has_label` (id)');
        $this->addSql('ALTER TABLE `observation_has_attachment` ADD CONSTRAINT FK_768BDB341409DD88 FOREIGN KEY (observation_id) REFERENCES Observation (id)');
        $this->addSql('ALTER TABLE `Group` DROP FOREIGN KEY FK_6DC044C57E3C61F9');
        $this->addSql('ALTER TABLE `Group` DROP FOREIGN KEY FK_6DC044C57E9E4C8C');
        $this->addSql('ALTER TABLE `Group` CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('DROP INDEX uniq_6dc044c59b9cabf4 ON `Group`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC016BC19B9CABF4 ON `Group` (grouptag)');
        $this->addSql('DROP INDEX idx_6dc044c57e3c61f9 ON `Group`');
        $this->addSql('CREATE INDEX IDX_AC016BC17E3C61F9 ON `Group` (owner_id)');
        $this->addSql('DROP INDEX uniq_6dc044c57e9e4c8c ON `Group`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC016BC17E9E4C8C ON `Group` (photo_id)');
        $this->addSql('ALTER TABLE `Group` ADD CONSTRAINT FK_6DC044C57E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE `Group` ADD CONSTRAINT FK_6DC044C57E9E4C8C FOREIGN KEY (photo_id) REFERENCES group_has_media (id)');
        $this->addSql('ALTER TABLE group_has_user DROP FOREIGN KEY FK_416D0666A76ED395');
        $this->addSql('DROP INDEX idx_416d0666a76ed395 ON group_has_user');
        $this->addSql('CREATE INDEX IDX_D9FF4169A76ED395 ON group_has_user (user_id)');
        $this->addSql('ALTER TABLE group_has_user ADD CONSTRAINT FK_416D0666A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_has_map DROP FOREIGN KEY FK_FF60E94B7E3C61F9');
        $this->addSql('ALTER TABLE user_has_map DROP FOREIGN KEY FK_FF60E94B94A4C7D4');
        $this->addSql('ALTER TABLE user_has_map ADD CONSTRAINT FK_FF60E94B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_has_map ADD CONSTRAINT FK_FF60E94B94A4C7D4 FOREIGN KEY (device_id) REFERENCES Device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_has_device DROP FOREIGN KEY FK_826A67F97E3C61F9');
        $this->addSql('ALTER TABLE user_has_device DROP FOREIGN KEY FK_826A67F994A4C7D4');
        $this->addSql('ALTER TABLE user_has_device ADD CONSTRAINT FK_826A67F97E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_has_device ADD CONSTRAINT FK_826A67F994A4C7D4 FOREIGN KEY (device_id) REFERENCES Device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE User CHANGE birthday birthday DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_has_media DROP FOREIGN KEY FK_91453C567E3C61F9');
        $this->addSql('ALTER TABLE user_has_media ADD CONSTRAINT FK_91453C567E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Parameters DROP FOREIGN KEY FK_8B546E345673F40A');
        $this->addSql('DROP INDEX uniq_8b546e345673f40a ON Parameters');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_49CE4B2E5673F40A ON Parameters (publicationSharing_id)');
        $this->addSql('ALTER TABLE Parameters ADD CONSTRAINT FK_8B546E345673F40A FOREIGN KEY (publicationSharing_id) REFERENCES Sharing (id)');
        $this->addSql('ALTER TABLE session CHANGE session_value session_value LONGBLOB NOT NULL');
        $this->addSql('ALTER TABLE Observation CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE publication_comment_has_action DROP FOREIGN KEY FK_CBE09F06A76ED395');
        $this->addSql('ALTER TABLE publication_comment_has_action DROP FOREIGN KEY FK_CBE09F06F8697D13');
        $this->addSql('DROP INDEX idx_cbe09f06f8697d13 ON publication_comment_has_action');
        $this->addSql('CREATE INDEX IDX_80639A5DF8697D13 ON publication_comment_has_action (comment_id)');
        $this->addSql('DROP INDEX idx_cbe09f06a76ed395 ON publication_comment_has_action');
        $this->addSql('CREATE INDEX IDX_80639A5DA76ED395 ON publication_comment_has_action (user_id)');
        $this->addSql('ALTER TABLE publication_comment_has_action ADD CONSTRAINT FK_CBE09F06A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE publication_comment_has_action ADD CONSTRAINT FK_CBE09F06F8697D13 FOREIGN KEY (comment_id) REFERENCES publication_has_comment (id)');
        $this->addSql('ALTER TABLE publication_has_action DROP FOREIGN KEY FK_78AA084638B217A7');
        $this->addSql('ALTER TABLE publication_has_action DROP FOREIGN KEY FK_78AA0846A76ED395');
        $this->addSql('DROP INDEX idx_78aa084638b217a7 ON publication_has_action');
        $this->addSql('CREATE INDEX IDX_599DEE838B217A7 ON publication_has_action (publication_id)');
        $this->addSql('DROP INDEX idx_78aa0846a76ed395 ON publication_has_action');
        $this->addSql('CREATE INDEX IDX_599DEE8A76ED395 ON publication_has_action (user_id)');
        $this->addSql('ALTER TABLE publication_has_action ADD CONSTRAINT FK_78AA084638B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE publication_has_action ADD CONSTRAINT FK_78AA0846A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE publication_has_report DROP FOREIGN KEY FK_867A25FEA76ED395');
        $this->addSql('ALTER TABLE publication_has_report CHANGE created created DATETIME NOT NULL');
        $this->addSql('ALTER TABLE publication_has_report ADD CONSTRAINT FK_867A25FEA76ED395 FOREIGN KEY (user_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Publication ADD locality_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Publication ADD CONSTRAINT FK_29A0E8AE88823A92 FOREIGN KEY (locality_id) REFERENCES `locality` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_29A0E8AE88823A92 ON Publication (locality_id)');
        $this->addSql('ALTER TABLE publication_has_comment DROP FOREIGN KEY FK_BE13C94938B217A7');
        $this->addSql('ALTER TABLE publication_has_comment DROP FOREIGN KEY FK_BE13C9497E3C61F9');
        $this->addSql('DROP INDEX idx_be13c94938b217a7 ON publication_has_comment');
        $this->addSql('CREATE INDEX IDX_24E69F1C38B217A7 ON publication_has_comment (publication_id)');
        $this->addSql('DROP INDEX idx_be13c9497e3c61f9 ON publication_has_comment');
        $this->addSql('CREATE INDEX IDX_24E69F1C7E3C61F9 ON publication_has_comment (owner_id)');
        $this->addSql('ALTER TABLE publication_has_comment ADD CONSTRAINT FK_BE13C94938B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE publication_has_comment ADD CONSTRAINT FK_BE13C9497E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_5C4C4134CD53EDB6');
        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_5C4C4134EF1A9D84');
        $this->addSql('DROP INDEX idx_5c4c4134ef1a9d84 ON notification_has_receiver');
        $this->addSql('CREATE INDEX IDX_2DD09C97EF1A9D84 ON notification_has_receiver (notification_id)');
        $this->addSql('DROP INDEX idx_5c4c4134cd53edb6 ON notification_has_receiver');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_2DD09C97CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2DD09C97CD53EDB6 ON notification_has_receiver (receiver_id)');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134EF1A9D84 FOREIGN KEY (notification_id) REFERENCES Notification (id)');
        $this->addSql('ALTER TABLE graph_has_pertinence CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE zone CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE card CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE card_has_label CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE card_category_by_zone DROP FOREIGN KEY FK_C23B01464ACC9A20');
        $this->addSql('ALTER TABLE card_category_by_zone DROP FOREIGN KEY FK_C23B01469F2C3FAB');
        $this->addSql('ALTER TABLE card_category_by_zone CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE card_category_by_zone ADD CONSTRAINT FK_C23B01464ACC9A20 FOREIGN KEY (card_id) REFERENCES `card` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_category_by_zone ADD CONSTRAINT FK_C23B01469F2C3FAB FOREIGN KEY (zone_id) REFERENCES `zone` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE animal ADD parent_id INT DEFAULT NULL, ADD lft INT NOT NULL, ADD rgt INT NOT NULL, ADD root INT DEFAULT NULL, ADD lvl INT NOT NULL, CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F727ACA70 FOREIGN KEY (parent_id) REFERENCES `animal` (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6AAB231F727ACA70 ON animal (parent_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ReceiverUser');

        $this->addSql('DROP TABLE `observation_has_attachment`');
        $this->addSql('ALTER TABLE `Group` DROP FOREIGN KEY FK_AC016BC17E3C61F9');
        $this->addSql('ALTER TABLE `Group` DROP FOREIGN KEY FK_AC016BC17E9E4C8C');
        $this->addSql('ALTER TABLE `Group` CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('DROP INDEX uniq_ac016bc19b9cabf4 ON `Group`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6DC044C59B9CABF4 ON `Group` (grouptag)');
        $this->addSql('DROP INDEX uniq_ac016bc17e9e4c8c ON `Group`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6DC044C57E9E4C8C ON `Group` (photo_id)');
        $this->addSql('DROP INDEX idx_ac016bc17e3c61f9 ON `Group`');
        $this->addSql('CREATE INDEX IDX_6DC044C57E3C61F9 ON `Group` (owner_id)');
        $this->addSql('ALTER TABLE `Group` ADD CONSTRAINT FK_AC016BC17E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE `Group` ADD CONSTRAINT FK_AC016BC17E9E4C8C FOREIGN KEY (photo_id) REFERENCES group_has_media (id)');
        $this->addSql('ALTER TABLE Observation CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE Parameters DROP FOREIGN KEY FK_49CE4B2E5673F40A');
        $this->addSql('DROP INDEX uniq_49ce4b2e5673f40a ON Parameters');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8B546E345673F40A ON Parameters (publicationSharing_id)');
        $this->addSql('ALTER TABLE Parameters ADD CONSTRAINT FK_49CE4B2E5673F40A FOREIGN KEY (publicationSharing_id) REFERENCES Sharing (id)');
        $this->addSql('ALTER TABLE Publication DROP FOREIGN KEY FK_29A0E8AE88823A92');
        $this->addSql('DROP INDEX IDX_29A0E8AE88823A92 ON Publication');
        $this->addSql('ALTER TABLE Publication DROP locality_id');
        $this->addSql('ALTER TABLE User CHANGE birthday birthday DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE `animal` DROP FOREIGN KEY FK_6AAB231F727ACA70');
        $this->addSql('DROP INDEX IDX_6AAB231F727ACA70 ON `animal`');
        $this->addSql('ALTER TABLE `animal` DROP parent_id, DROP lft, DROP rgt, DROP root, DROP lvl, CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE `card` CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE `card_category_by_zone` DROP FOREIGN KEY FK_C23B01464ACC9A20');
        $this->addSql('ALTER TABLE `card_category_by_zone` DROP FOREIGN KEY FK_C23B01469F2C3FAB');
        $this->addSql('ALTER TABLE `card_category_by_zone` CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE `card_category_by_zone` ADD CONSTRAINT FK_C23B01464ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE `card_category_by_zone` ADD CONSTRAINT FK_C23B01469F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE `card_has_label` CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE graph_has_pertinence CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
        $this->addSql('ALTER TABLE group_has_user DROP FOREIGN KEY FK_D9FF4169A76ED395');
        $this->addSql('DROP INDEX idx_d9ff4169a76ed395 ON group_has_user');
        $this->addSql('CREATE INDEX IDX_416D0666A76ED395 ON group_has_user (user_id)');
        $this->addSql('ALTER TABLE group_has_user ADD CONSTRAINT FK_D9FF4169A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_2DD09C97CD53EDB6');
        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_2DD09C97EF1A9D84');
        $this->addSql('ALTER TABLE notification_has_receiver DROP FOREIGN KEY FK_2DD09C97CD53EDB6');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_5C4C4134CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id)');
        $this->addSql('DROP INDEX idx_2dd09c97ef1a9d84 ON notification_has_receiver');
        $this->addSql('CREATE INDEX IDX_5C4C4134EF1A9D84 ON notification_has_receiver (notification_id)');
        $this->addSql('DROP INDEX idx_2dd09c97cd53edb6 ON notification_has_receiver');
        $this->addSql('CREATE INDEX IDX_5C4C4134CD53EDB6 ON notification_has_receiver (receiver_id)');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_2DD09C97EF1A9D84 FOREIGN KEY (notification_id) REFERENCES Notification (id)');
        $this->addSql('ALTER TABLE notification_has_receiver ADD CONSTRAINT FK_2DD09C97CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES User (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_comment_has_action DROP FOREIGN KEY FK_80639A5DF8697D13');
        $this->addSql('ALTER TABLE publication_comment_has_action DROP FOREIGN KEY FK_80639A5DA76ED395');
        $this->addSql('DROP INDEX idx_80639a5df8697d13 ON publication_comment_has_action');
        $this->addSql('CREATE INDEX IDX_CBE09F06F8697D13 ON publication_comment_has_action (comment_id)');
        $this->addSql('DROP INDEX idx_80639a5da76ed395 ON publication_comment_has_action');
        $this->addSql('CREATE INDEX IDX_CBE09F06A76ED395 ON publication_comment_has_action (user_id)');
        $this->addSql('ALTER TABLE publication_comment_has_action ADD CONSTRAINT FK_80639A5DF8697D13 FOREIGN KEY (comment_id) REFERENCES publication_has_comment (id)');
        $this->addSql('ALTER TABLE publication_comment_has_action ADD CONSTRAINT FK_80639A5DA76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE publication_has_action DROP FOREIGN KEY FK_599DEE838B217A7');
        $this->addSql('ALTER TABLE publication_has_action DROP FOREIGN KEY FK_599DEE8A76ED395');
        $this->addSql('DROP INDEX idx_599dee838b217a7 ON publication_has_action');
        $this->addSql('CREATE INDEX IDX_78AA084638B217A7 ON publication_has_action (publication_id)');
        $this->addSql('DROP INDEX idx_599dee8a76ed395 ON publication_has_action');
        $this->addSql('CREATE INDEX IDX_78AA0846A76ED395 ON publication_has_action (user_id)');
        $this->addSql('ALTER TABLE publication_has_action ADD CONSTRAINT FK_599DEE838B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE publication_has_action ADD CONSTRAINT FK_599DEE8A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE publication_has_comment DROP FOREIGN KEY FK_24E69F1C38B217A7');
        $this->addSql('ALTER TABLE publication_has_comment DROP FOREIGN KEY FK_24E69F1C7E3C61F9');
        $this->addSql('DROP INDEX idx_24e69f1c38b217a7 ON publication_has_comment');
        $this->addSql('CREATE INDEX IDX_BE13C94938B217A7 ON publication_has_comment (publication_id)');
        $this->addSql('DROP INDEX idx_24e69f1c7e3c61f9 ON publication_has_comment');
        $this->addSql('CREATE INDEX IDX_BE13C9497E3C61F9 ON publication_has_comment (owner_id)');
        $this->addSql('ALTER TABLE publication_has_comment ADD CONSTRAINT FK_24E69F1C38B217A7 FOREIGN KEY (publication_id) REFERENCES Publication (id)');
        $this->addSql('ALTER TABLE publication_has_comment ADD CONSTRAINT FK_24E69F1C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE `publication_has_report` DROP FOREIGN KEY FK_867A25FEA76ED395');
        $this->addSql('ALTER TABLE `publication_has_report` CHANGE created created DATE NOT NULL');
        $this->addSql('ALTER TABLE `publication_has_report` ADD CONSTRAINT FK_867A25FEA76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE Session CHANGE session_value session_value TEXT NOT NULL COLLATE utf8_general_ci');
        $this->addSql('ALTER TABLE user_has_device DROP FOREIGN KEY FK_826A67F994A4C7D4');
        $this->addSql('ALTER TABLE user_has_device DROP FOREIGN KEY FK_826A67F97E3C61F9');
        $this->addSql('ALTER TABLE user_has_device ADD CONSTRAINT FK_826A67F994A4C7D4 FOREIGN KEY (device_id) REFERENCES Device (id)');
        $this->addSql('ALTER TABLE user_has_device ADD CONSTRAINT FK_826A67F97E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_has_map DROP FOREIGN KEY FK_FF60E94B94A4C7D4');
        $this->addSql('ALTER TABLE user_has_map DROP FOREIGN KEY FK_FF60E94B7E3C61F9');
        $this->addSql('ALTER TABLE user_has_map ADD CONSTRAINT FK_FF60E94B94A4C7D4 FOREIGN KEY (device_id) REFERENCES Device (id)');
        $this->addSql('ALTER TABLE user_has_map ADD CONSTRAINT FK_FF60E94B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_has_media DROP FOREIGN KEY FK_91453C567E3C61F9');
        $this->addSql('ALTER TABLE user_has_media ADD CONSTRAINT FK_91453C567E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE `zone` CHANGE created created DATE NOT NULL, CHANGE updated updated DATE NOT NULL');
    }

}
