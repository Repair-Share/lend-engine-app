<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221152719 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table deposit drop foreign key FK_95DB9D3978219C8F');
        $this->addSql('alter table deposit drop key UNIQ_95DB9D3978219C8F');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table deposit add constraint FK_95DB9D3978219C8F foreign key (loan_row_id) references loan_row (id)');
        $this->addSql('alter table deposit add unique index UNIQ_95DB9D3978219C8F (loan_row_id)');
    }
}
