<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191113095539 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE product_section (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, show_on_website TINYINT(1) DEFAULT \'1\' NOT NULL, sort INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_tag ADD section_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_tag ADD CONSTRAINT FK_E3A6E39CD823E37A FOREIGN KEY (section_id) REFERENCES product_section (id)');
        $this->addSql('CREATE INDEX IDX_E3A6E39CD823E37A ON product_tag (section_id)');
    }

    public function down(Schema $schema) : void
    {

    }
}
