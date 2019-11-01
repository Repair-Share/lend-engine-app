<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191025155432 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE kit_component (item_id INT NOT NULL, component_id INT NOT NULL, component_quantity INT NOT NULL, INDEX IDX_FAA63CAB126F525E (item_id), INDEX IDX_FAA63CABE2ABAFFF (component_id), PRIMARY KEY(item_id, component_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kit_component ADD CONSTRAINT FK_FAA63CAB126F525E FOREIGN KEY (item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE kit_component ADD CONSTRAINT FK_FAA63CABE2ABAFFF FOREIGN KEY (component_id) REFERENCES inventory_item (id)');
        $this->addSql("ALTER TABLE inventory_item ADD item_type VARCHAR(16) NOT NULL DEFAULT 'loan'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL COLLATE utf8_unicode_ci, object_class VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, field VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, foreign_key VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci, content LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), INDEX translations_lookup_idx (locale, object_class, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE kit_component');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7de12ab56 TO IDX_EA741FC3DE12AB56');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7f6bd1646 TO IDX_EA741FC3F6BD1646');
        $this->addSql('ALTER TABLE inventory_item DROP type');
        $this->addSql('ALTER TABLE membership_type CHANGE is_active is_active TINYINT(1) DEFAULT \'1\' NOT NULL');
    }
}
