<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231024180745 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table site add default_forward_pick_location int default null after default_check_in_location;');
        $this->addSql('alter table site add constraint FK_701254E54217805 foreign key (default_forward_pick_location) references inventory_location (id) on delete cascade;');
        $this->addSql('alter table site add constraint UNIQ_701254E54217805 unique (default_forward_pick_location);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table site drop foreign key FK_701254E54217805;');
        $this->addSql('alter table site drop key UNIQ_701254E54217805;');
        $this->addSql('alter table site drop column default_forward_pick_location;');
    }
}
