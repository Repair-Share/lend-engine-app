<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191110160256 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE contact ADD secure_access_token VARCHAR(255) DEFAULT NULL, DROP facebook_id, DROP facebook_access_token, DROP google_id, DROP google_access_token, DROP twitter_id, DROP twitter_access_token, DROP twitter_access_token_secret');
    }

    public function down(Schema $schema) : void
    {

    }
}
