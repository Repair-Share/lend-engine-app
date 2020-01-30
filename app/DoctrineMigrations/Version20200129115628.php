<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200129115628 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE item_movement ADD quantity INT DEFAULT NULL');

        $this->addSql('ALTER TABLE loan_row ADD item_location INT DEFAULT NULL');
        $this->addSql('ALTER TABLE loan_row ADD CONSTRAINT FK_922D737F32934100 FOREIGN KEY (item_location) REFERENCES inventory_location (id)');
        $this->addSql('CREATE INDEX IDX_922D737F32934100 ON loan_row (item_location)');
    }

    public function down(Schema $schema) : void
    {

    }
}
