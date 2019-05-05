<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190426065901 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO page
        (created_by, updated_by, created_at, updated_at, name, title, content, visibility, sort)
        VALUES
        (null, null, NOW(), NOW(), 'Home', 'Home', IFNULL((SELECT setup_value FROM setting WHERE setup_key = 'site_welcome'), ''), 'PUBLIC', -1)
        ");

        $this->addSql("UPDATE page SET content = REPLACE(content, '\r\n', '<br />')");
    }

    public function down(Schema $schema) : void
    {

    }
}
