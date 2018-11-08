<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161005213940 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE check_in_prompt (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, sort INT DEFAULT NULL, default_on TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_7FFF77DD5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE check_out_prompt (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, sort INT DEFAULT NULL, default_on TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_CC365FBC5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item_check_in_prompt (inventory_item_id INT NOT NULL, check_in_prompt_id INT NOT NULL, INDEX IDX_137042F3536BF4A2 (inventory_item_id), INDEX IDX_137042F3AE6C6390 (check_in_prompt_id), PRIMARY KEY(inventory_item_id, check_in_prompt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item_check_out_prompt (inventory_item_id INT NOT NULL, check_out_prompt_id INT NOT NULL, INDEX IDX_108CDD46536BF4A2 (inventory_item_id), INDEX IDX_108CDD46589C168A (check_out_prompt_id), PRIMARY KEY(inventory_item_id, check_out_prompt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE inventory_item_check_in_prompt ADD CONSTRAINT FK_137042F3536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_item_check_in_prompt ADD CONSTRAINT FK_137042F3AE6C6390 FOREIGN KEY (check_in_prompt_id) REFERENCES check_in_prompt (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_item_check_out_prompt ADD CONSTRAINT FK_108CDD46536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_item_check_out_prompt ADD CONSTRAINT FK_108CDD46589C168A FOREIGN KEY (check_out_prompt_id) REFERENCES check_out_prompt (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4C62E638C05FB297 ON contact (confirmation_token(20))');
        $this->addSql('ALTER TABLE inventory_item DROP serial_required');
        $this->addSql('ALTER TABLE note ADD admin_only INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_tag ADD show_on_website TINYINT(1) DEFAULT \'1\' NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE inventory_item_check_in_prompt DROP FOREIGN KEY FK_137042F3AE6C6390');
        $this->addSql('ALTER TABLE inventory_item_check_out_prompt DROP FOREIGN KEY FK_108CDD46589C168A');
        $this->addSql('DROP TABLE check_in_prompt');
        $this->addSql('DROP TABLE check_out_prompt');
        $this->addSql('DROP TABLE inventory_item_check_in_prompt');
        $this->addSql('DROP TABLE inventory_item_check_out_prompt');
        $this->addSql('DROP INDEX UNIQ_4C62E638C05FB297 ON contact');
        $this->addSql('ALTER TABLE inventory_item ADD serial_required TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE note DROP admin_only');
        $this->addSql('ALTER TABLE product_tag DROP show_on_website');
    }
}
