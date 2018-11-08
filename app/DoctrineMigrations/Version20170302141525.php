<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170302141525 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE site_opening (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, week_day INT NOT NULL, time_from VARCHAR(4) NOT NULL, time_to VARCHAR(4) NOT NULL, INDEX IDX_F5913608F6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE site_opening ADD CONSTRAINT FK_F5913608F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');

//        $this->addSql('INSERT INTO site_opening (site_id, week_day, time_from, time_to) VALUES (1, 1, "0900", "1700");');
//        $this->addSql('INSERT INTO site_opening (site_id, week_day, time_from, time_to) VALUES (1, 2, "0900", "1700");');
//        $this->addSql('INSERT INTO site_opening (site_id, week_day, time_from, time_to) VALUES (1, 3, "0900", "1700");');
//        $this->addSql('INSERT INTO site_opening (site_id, week_day, time_from, time_to) VALUES (1, 4, "0900", "1700");');
//        $this->addSql('INSERT INTO site_opening (site_id, week_day, time_from, time_to) VALUES (1, 5, "0900", "1700");');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE site_opening');

    }
}
