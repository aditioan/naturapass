<?php

namespace NaturaPass\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160302140700 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `hunt_type` (`id`, `name`, `created`, `updated`) VALUES (1, 'A la hutte / Gabion', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(2, 'Autres Chasses du gibier d''eau', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(3, 'Battue', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(4, 'Chasse à l''affût (Grand gibier)', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(5, 'Chasse à l''arc', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(6, 'Chasse à la billebaude', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(7, 'Chasse à la passée', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(8, 'Chasse au chien d''arrêt', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(9, 'Chasse au vol', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(10, 'Chasse aux appelants', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(11, 'Chasse de montagne', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(12, 'Grande vénerie', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(13, 'Passée aux grives', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(14, 'Poussé silencieuse', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(15, 'Approche / Pirsch', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(16, 'Battue aux petits gibiers', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(17, 'Chasse à l''arc', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(18, 'Chasse au furet', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(19, 'Chasse aux gluaux', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(20, 'Palombière', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(21, 'Piégeage', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(22, 'Petite vénerie', '2016-03-02 14:05:00', '2016-03-02 14:05:00'),(23, 'Vénerie sous terre', '2016-03-02 14:05:00', '2016-03-02 14:05:00');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
