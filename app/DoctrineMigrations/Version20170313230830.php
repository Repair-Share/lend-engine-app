<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170313230830 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE loan_row ADD site_from INT DEFAULT NULL, ADD site_to INT DEFAULT NULL');
        $this->addSql('ALTER TABLE loan_row ADD CONSTRAINT FK_922D737F82801C89 FOREIGN KEY (site_from) REFERENCES site (id)');
        $this->addSql('ALTER TABLE loan_row ADD CONSTRAINT FK_922D737F9E03A3D2 FOREIGN KEY (site_to) REFERENCES site (id)');
        $this->addSql('CREATE INDEX IDX_922D737F82801C89 ON loan_row (site_from)');
        $this->addSql('CREATE INDEX IDX_922D737F9E03A3D2 ON loan_row (site_to)');

        // Update all existing rows
        $this->addSql('UPDATE loan_row SET site_from = 1;');
        $this->addSql('UPDATE loan_row SET site_to = 1;');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE loan_row DROP FOREIGN KEY FK_922D737F82801C89');
        $this->addSql('ALTER TABLE loan_row DROP FOREIGN KEY FK_922D737F9E03A3D2');
        $this->addSql('DROP INDEX IDX_922D737F82801C89 ON loan_row');
        $this->addSql('DROP INDEX IDX_922D737F9E03A3D2 ON loan_row');
        $this->addSql('ALTER TABLE loan_row DROP site_from, DROP site_to');
    }
}
