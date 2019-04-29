<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190429211412 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, code VARCHAR(32) NOT NULL, description VARCHAR(2055) NOT NULL, thumbnail VARCHAR(255) DEFAULT NULL, font VARCHAR(32) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, UNIQUE INDEX UNIQ_9775E7085E237E06 (name), UNIQUE INDEX UNIQ_9775E70877153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO theme (name, code, description, price) VALUES ("Original", "default", "Left hand menu.", "0")');
        $this->addSql('INSERT INTO theme (name, code, description, price) VALUES ("Top menu", "top_menu", "Top menu.", "0")');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE theme');
    }
}
