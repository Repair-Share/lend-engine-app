<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190614220134 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE attendee (id INT AUTO_INCREMENT NOT NULL, event_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, created_by INT DEFAULT NULL, created_at DATETIME NOT NULL, is_confirmed TINYINT(1) NOT NULL, type VARCHAR(16) NOT NULL, INDEX IDX_1150D56771F7E88B (event_id), INDEX IDX_1150D567E7A1254A (contact_id), INDEX IDX_1150D567DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attendee ADD CONSTRAINT FK_1150D56771F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE attendee ADD CONSTRAINT FK_1150D567E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE attendee ADD CONSTRAINT FK_1150D567DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
//        $this->addSql('ALTER TABLE event RENAME INDEX idx_ea741fc3f6bd1646 TO IDX_3BAE0AA7F6BD1646');
//        $this->addSql('ALTER TABLE event RENAME INDEX idx_ea741fc3de12ab56 TO IDX_3BAE0AA7DE12AB56');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE attendee');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7de12ab56 TO IDX_EA741FC3DE12AB56');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7f6bd1646 TO IDX_EA741FC3F6BD1646');
    }
}
