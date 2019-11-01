<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191101140535 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE inventory_item ADD donated_by INT DEFAULT NULL, ADD owned_by INT DEFAULT NULL, CHANGE item_type item_type VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA303C2B095F FOREIGN KEY (donated_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA308BBCDCA8 FOREIGN KEY (owned_by) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_55BDEA303C2B095F ON inventory_item (donated_by)');
        $this->addSql('CREATE INDEX IDX_55BDEA308BBCDCA8 ON inventory_item (owned_by)');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL COLLATE utf8_unicode_ci, object_class VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, field VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, foreign_key VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci, content LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), INDEX translations_lookup_idx (locale, object_class, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7de12ab56 TO IDX_EA741FC3DE12AB56');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7f6bd1646 TO IDX_EA741FC3F6BD1646');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA303C2B095F');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA308BBCDCA8');
        $this->addSql('DROP INDEX IDX_55BDEA303C2B095F ON inventory_item');
        $this->addSql('DROP INDEX IDX_55BDEA308BBCDCA8 ON inventory_item');
        $this->addSql('ALTER TABLE inventory_item DROP donated_by, DROP owned_by, CHANGE item_type item_type VARCHAR(16) DEFAULT \'loan\' NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE membership_type CHANGE is_active is_active TINYINT(1) DEFAULT \'1\' NOT NULL');
    }
}
