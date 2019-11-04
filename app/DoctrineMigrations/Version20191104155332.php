<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191104155332 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE maintenance (id INT AUTO_INCREMENT NOT NULL, completed_by INT DEFAULT NULL, inventory_item_id INT NOT NULL, maintenance_plan_id INT NOT NULL, assigned_to INT DEFAULT NULL, status VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, due_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, total_cost NUMERIC(10, 2) NOT NULL, notes VARCHAR(2055) DEFAULT NULL, INDEX IDX_2F84F8E9192FE44 (completed_by), INDEX IDX_2F84F8E9536BF4A2 (inventory_item_id), INDEX IDX_2F84F8E9916F4709 (maintenance_plan_id), INDEX IDX_2F84F8E989EEAF91 (assigned_to), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item_maintenance_plan (inventory_item_id INT NOT NULL, maintenance_plan_id INT NOT NULL, INDEX IDX_CEF3B94B536BF4A2 (inventory_item_id), INDEX IDX_CEF3B94B916F4709 (maintenance_plan_id), PRIMARY KEY(inventory_item_id, maintenance_plan_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, description VARCHAR(1024) DEFAULT NULL, is_active TINYINT(1) NOT NULL, interval_months INT DEFAULT NULL, after_each_loan TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9192FE44 FOREIGN KEY (completed_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9916F4709 FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan (id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E989EEAF91 FOREIGN KEY (assigned_to) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE inventory_item_maintenance_plan ADD CONSTRAINT FK_CEF3B94B536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_item_maintenance_plan ADD CONSTRAINT FK_CEF3B94B916F4709 FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE file_attachment ADD maintenance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file_attachment ADD CONSTRAINT FK_C0B7020DF6C202BC FOREIGN KEY (maintenance_id) REFERENCES maintenance (id)');
        $this->addSql('CREATE INDEX IDX_C0B7020DF6C202BC ON file_attachment (maintenance_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file_attachment DROP FOREIGN KEY FK_C0B7020DF6C202BC');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E9916F4709');
        $this->addSql('ALTER TABLE inventory_item_maintenance_plan DROP FOREIGN KEY FK_CEF3B94B916F4709');
        $this->addSql('CREATE TABLE ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL COLLATE utf8_unicode_ci, object_class VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, field VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, foreign_key VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci, content LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), INDEX translations_lookup_idx (locale, object_class, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE maintenance');
        $this->addSql('DROP TABLE inventory_item_maintenance_plan');
        $this->addSql('DROP TABLE maintenance_plan');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7de12ab56 TO IDX_EA741FC3DE12AB56');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7f6bd1646 TO IDX_EA741FC3F6BD1646');
        $this->addSql('DROP INDEX IDX_C0B7020DF6C202BC ON file_attachment');
        $this->addSql('ALTER TABLE file_attachment DROP maintenance_id');
        $this->addSql('ALTER TABLE membership_type CHANGE is_active is_active TINYINT(1) DEFAULT \'1\' NOT NULL');
    }
}
