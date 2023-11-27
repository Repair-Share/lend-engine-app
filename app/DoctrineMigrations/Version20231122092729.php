<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231122092729 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table site add lat decimal(11, 8) null after post_code;');
        $this->addSql('alter table site add `lng` decimal(11, 8) null after lat;');
        $this->addSql('alter table site add geocoded_string text null after lng;');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table site drop column lat;');
        $this->addSql('alter table site drop column `lng`;');
        $this->addSql('alter table site drop column geocoded_string;');
    }
}
