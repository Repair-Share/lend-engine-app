<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190617212310 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE event ADD is_bookable TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE attendee ADD price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE payment ADD event_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D71F7E88B ON payment (event_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attendee DROP price');
        $this->addSql('ALTER TABLE event DROP is_bookable');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D71F7E88B');
        $this->addSql('DROP INDEX IDX_6D28840D71F7E88B ON payment');
        $this->addSql('ALTER TABLE payment DROP event_id');
    }
}
