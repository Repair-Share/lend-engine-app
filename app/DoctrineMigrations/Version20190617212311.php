<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190617212311 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $locales = ['fr', 'sv-SE', 'sk', 'de', 'es', 'is', 'nl', 'ro', 'cy'];

        foreach ($locales AS $l) {

            $this->addSql("UPDATE inventory_item i
        LEFT JOIN ext_translations tr ON tr.foreign_key = i.id AND tr.field = 'name'
        SET i.name = tr.content
        WHERE tr.content IS NOT NULL
        AND tr.locale = '{$l}';");

            $this->addSql("UPDATE inventory_item i
        LEFT JOIN ext_translations tr ON tr.foreign_key = i.id AND tr.field = 'description'
        SET i.description = tr.content
        WHERE tr.content IS NOT NULL
        AND tr.locale = '{$l}';");

            $this->addSql("UPDATE inventory_item i
        LEFT JOIN ext_translations tr ON tr.foreign_key = i.id AND tr.field = 'care_information'
        SET i.care_information = tr.content
        WHERE tr.content IS NOT NULL
        AND tr.locale = '{$l}';");

            $this->addSql("UPDATE product_tag t
        LEFT JOIN ext_translations tr ON tr.foreign_key = t.id AND tr.field = 'name' AND tr.object_class = 'AppBundle\Entity\ProductTag'
        SET t.name = tr.content
        WHERE tr.content IS NOT NULL
        AND tr.locale = '{$l}';");

        }

    }

public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
