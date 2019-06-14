<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190614094114 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE opening_time_exception TO event;');

        $this->addSql('ALTER TABLE event ADD created_by INT DEFAULT NULL, ADD title VARCHAR(256) DEFAULT NULL, ADD facebook_url VARCHAR(256) DEFAULT NULL, ADD status VARCHAR(16) NOT NULL, ADD description VARCHAR(1024) DEFAULT NULL, ADD price NUMERIC(10, 2) NOT NULL, ADD max_attendees INT NOT NULL, ADD created_at DATETIME NOT NULL DEFAULT "2019-01-01 00:00:00"');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_EA741FC3DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_EA741FC3DE12AB56 ON event (created_by)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE opening_time_exception DROP FOREIGN KEY FK_EA741FC3DE12AB56');
        $this->addSql('DROP INDEX IDX_EA741FC3DE12AB56 ON opening_time_exception');
        $this->addSql('ALTER TABLE opening_time_exception DROP created_by, DROP title, DROP facebook_url, DROP status, DROP description, DROP price, DROP max_attendees, DROP created_at');
    }
}
