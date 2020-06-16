<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200616205144 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE app (id INT AUTO_INCREMENT NOT NULL, installed_at DATETIME NOT NULL, uninstalled_at DATETIME DEFAULT NULL, is_active TINYINT(1) NOT NULL, code VARCHAR(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE app_setting (setup_key VARCHAR(128) NOT NULL, app_id INT NOT NULL, setup_value VARCHAR(2056) NOT NULL, INDEX IDX_722938D57987212D (app_id), PRIMARY KEY(app_id, setup_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE app_setting ADD CONSTRAINT FK_722938D57987212D FOREIGN KEY (app_id) REFERENCES app (id)');

        // Migrate Mailchimp
        $this->addSql("INSERT INTO app (code, installed_at, is_active) SELECT 'mailchimp', NOW(), true FROM setting where setup_key = 'mailchimp_api_key' AND setup_value != '' AND setup_value IS NOT NULL");
        $this->addSql("INSERT INTO app_setting (app_id, setup_key, setup_value) SELECT 1, 'api_key', setup_value from setting where setup_key = 'mailchimp_api_key' AND setup_value != '' AND setup_value IS NOT NULL");
        $this->addSql("INSERT INTO app_setting (app_id, setup_key, setup_value) SELECT 1, 'list_id', setup_value from setting where setup_key = 'mailchimp_default_list_id' AND setup_value != '' AND setup_value IS NOT NULL");
        $this->addSql("INSERT INTO app_setting (app_id, setup_key, setup_value) SELECT 1, 'opt_in', setup_value from setting where setup_key = 'mailchimp_double_optin' AND setup_value != '' AND setup_value IS NOT NULL");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE app_setting DROP FOREIGN KEY FK_722938D57987212D');
        $this->addSql('CREATE TABLE ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, object_class VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, field VARCHAR(32) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, foreign_key VARCHAR(64) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, content LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, INDEX translations_lookup_idx (locale, object_class, foreign_key), UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE app');
        $this->addSql('DROP TABLE app_setting');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7de12ab56 TO IDX_EA741FC3DE12AB56');
        $this->addSql('ALTER TABLE event RENAME INDEX idx_3bae0aa7f6bd1646 TO IDX_EA741FC3F6BD1646');
        $this->addSql('ALTER TABLE membership_type CHANGE is_active is_active TINYINT(1) DEFAULT \'1\' NOT NULL');
    }
}
