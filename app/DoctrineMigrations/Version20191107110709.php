<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191107110709 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE maintenance_plan ADD provider INT DEFAULT NULL, ADD prevent_borrows TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE maintenance_plan ADD CONSTRAINT FK_12493BB192C4739C FOREIGN KEY (provider) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_12493BB192C4739C ON maintenance_plan (provider)');
    }

    public function down(Schema $schema) : void
    {

    }
}
