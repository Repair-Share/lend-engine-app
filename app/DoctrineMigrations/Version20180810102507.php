<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180810102507 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE deposit (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, contact_id INT DEFAULT NULL, loan_row_id INT DEFAULT NULL, created_at DATETIME NOT NULL, amount NUMERIC(10, 2) NOT NULL, balance NUMERIC(10, 2) NOT NULL, INDEX IDX_95DB9D39DE12AB56 (created_by), INDEX IDX_95DB9D39E7A1254A (contact_id), INDEX IDX_95DB9D3978219C8F (loan_row_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D39DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D39E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D3978219C8F FOREIGN KEY (loan_row_id) REFERENCES loan_row (id)');
        $this->addSql('ALTER TABLE inventory_item ADD deposit_amount NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD deposit_id INT DEFAULT NULL, ADD psp_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D9815E4B1 FOREIGN KEY (deposit_id) REFERENCES deposit (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D9815E4B1 ON payment (deposit_id)');
        $this->addSql('ALTER TABLE loan_row ADD deposit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE loan_row ADD CONSTRAINT FK_922D737F9815E4B1 FOREIGN KEY (deposit_id) REFERENCES deposit (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_922D737F9815E4B1 ON loan_row (deposit_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D9815E4B1');
        $this->addSql('ALTER TABLE loan_row DROP FOREIGN KEY FK_922D737F9815E4B1');
        $this->addSql('CREATE TABLE item_type (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE deposit');
        $this->addSql('ALTER TABLE inventory_item DROP deposit_amount');
        $this->addSql('DROP INDEX UNIQ_922D737F9815E4B1 ON loan_row');
        $this->addSql('ALTER TABLE loan_row DROP deposit_id');
        $this->addSql('DROP INDEX IDX_6D28840D9815E4B1 ON payment');
        $this->addSql('ALTER TABLE payment DROP deposit_id, DROP psp_code');
    }
}
