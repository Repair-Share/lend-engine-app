<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190416090739 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE item_type');
        $this->addSql('CREATE INDEX IDX_55BDEA3044EE13D2 ON inventory_item (item_type)');
        $this->addSql('DROP INDEX UNIQ_4C62E638C05FB297 ON contact');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4C62E638C05FB297 ON contact (confirmation_token)');
        $this->addSql('DROP INDEX UNIQ_9F74B898EB89A0AB ON setting');
        $this->addSql('ALTER TABLE site ADD is_listed TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE item_type (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP INDEX UNIQ_4C62E638C05FB297 ON contact');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4C62E638C05FB297 ON contact (confirmation_token(20))');
        $this->addSql('DROP INDEX IDX_55BDEA3044EE13D2 ON inventory_item');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9F74B898EB89A0AB ON setting (setup_key)');
        $this->addSql('ALTER TABLE site DROP is_listed');
    }
}
