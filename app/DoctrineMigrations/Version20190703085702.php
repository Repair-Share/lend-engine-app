<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190703085702 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deposit DROP INDEX IDX_95DB9D3978219C8F, ADD UNIQUE INDEX UNIQ_95DB9D3978219C8F (loan_row_id)');
        $this->addSql('ALTER TABLE event CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE membership_type ADD credit_limit NUMERIC(10, 2) DEFAULT NULL, ADD max_items INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deposit DROP INDEX UNIQ_95DB9D3978219C8F, ADD INDEX IDX_95DB9D3978219C8F (loan_row_id)');
        $this->addSql('ALTER TABLE event CHANGE created_at created_at DATETIME DEFAULT \'2019-01-01 00:00:00\' NOT NULL');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7de12ab56 TO IDX_EA741FC3DE12AB56');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7f6bd1646 TO IDX_EA741FC3F6BD1646');
        $this->addSql('ALTER TABLE membership_type DROP credit_limit, DROP max_items');
    }
}
