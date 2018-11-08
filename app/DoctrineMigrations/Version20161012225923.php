<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161012225923 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE inventory_item_site (inventory_item_id INT NOT NULL, site_id INT NOT NULL, INDEX IDX_26CFA477536BF4A2 (inventory_item_id), INDEX IDX_26CFA477F6BD1646 (site_id), PRIMARY KEY(inventory_item_id, site_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, default_check_in_location INT DEFAULT NULL, name VARCHAR(64) NOT NULL, is_active TINYINT(1) NOT NULL, address VARCHAR(255) DEFAULT NULL, country VARCHAR(2) NOT NULL, post_code VARCHAR(16) DEFAULT NULL, UNIQUE INDEX UNIQ_694309E45E237E06 (name), UNIQUE INDEX UNIQ_694309E42726301 (default_check_in_location), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE inventory_item_site ADD CONSTRAINT FK_26CFA477536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_item_site ADD CONSTRAINT FK_26CFA477F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E42726301 FOREIGN KEY (default_check_in_location) REFERENCES inventory_location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact ADD active_site INT DEFAULT NULL, ADD created_at_site INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E63887239B34 FOREIGN KEY (active_site) REFERENCES site (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E63894304B5C FOREIGN KEY (created_at_site) REFERENCES site (id)');
        $this->addSql('CREATE INDEX IDX_4C62E63887239B34 ON contact (active_site)');
        $this->addSql('CREATE INDEX IDX_4C62E63894304B5C ON contact (created_at_site)');
        $this->addSql('DROP INDEX UNIQ_EAD4335A5E237E06 ON inventory_location');
        $this->addSql('ALTER TABLE inventory_location ADD site INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory_location ADD CONSTRAINT FK_EAD4335A694309E4 FOREIGN KEY (site) REFERENCES site (id)');
        $this->addSql('CREATE INDEX IDX_EAD4335A694309E4 ON inventory_location (site)');
        $this->addSql('CREATE UNIQUE INDEX name_site_unique ON inventory_location (name, site)');
        $this->addSql('ALTER TABLE loan ADD created_at_site INT DEFAULT NULL');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D0394304B5C FOREIGN KEY (created_at_site) REFERENCES site (id)');
        $this->addSql('CREATE INDEX IDX_C5D30D0394304B5C ON loan (created_at_site)');

        $this->addSql('INSERT INTO site
          (
              name,
              is_active,
              address,
              country,
              post_code,
              default_check_in_location
          )
          VALUES
          (
              "Main site",
              1,
              IFNULL((SELECT setup_value FROM setting WHERE setup_key = "org_address"), "..."),
              IFNULL((SELECT setup_value FROM setting WHERE setup_key = "org_country"), "GB"),
              IFNULL((SELECT setup_value FROM setting WHERE setup_key = "org_postcode"), "..."),
              "2"
          )
          ');
        $this->addSql('UPDATE inventory_location SET site = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63887239B34');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63894304B5C');
        $this->addSql('ALTER TABLE inventory_item_site DROP FOREIGN KEY FK_26CFA477F6BD1646');
        $this->addSql('ALTER TABLE inventory_location DROP FOREIGN KEY FK_EAD4335A694309E4');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D0394304B5C');
        $this->addSql('DROP TABLE inventory_item_site');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP INDEX IDX_4C62E63887239B34 ON contact');
        $this->addSql('DROP INDEX IDX_4C62E63894304B5C ON contact');
        $this->addSql('ALTER TABLE contact DROP active_site, DROP created_at_site');
        $this->addSql('DROP INDEX IDX_EAD4335A694309E4 ON inventory_location');
        $this->addSql('DROP INDEX name_site_unique ON inventory_location');
        $this->addSql('ALTER TABLE inventory_location DROP site');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EAD4335A5E237E06 ON inventory_location (name)');
        $this->addSql('DROP INDEX IDX_C5D30D0394304B5C ON loan');
        $this->addSql('ALTER TABLE loan DROP created_at_site');
    }
}
