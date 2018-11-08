<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version0000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE child (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, name VARCHAR(64) DEFAULT NULL, date_of_birth DATE NOT NULL, gender VARCHAR(1) NOT NULL, INDEX IDX_22B35429E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, active_membership INT DEFAULT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, locked TINYINT(1) NOT NULL, expired TINYINT(1) NOT NULL, expires_at DATETIME DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', credentials_expired TINYINT(1) NOT NULL, credentials_expire_at DATETIME DEFAULT NULL, facebook_id VARCHAR(255) DEFAULT NULL, facebook_access_token VARCHAR(255) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, google_access_token VARCHAR(255) DEFAULT NULL, first_name VARCHAR(32) DEFAULT NULL, last_name VARCHAR(32) DEFAULT NULL, telephone VARCHAR(64) DEFAULT NULL, address_line_1 VARCHAR(255) DEFAULT NULL, address_line_2 VARCHAR(255) DEFAULT NULL, address_line_3 VARCHAR(255) DEFAULT NULL, address_line_4 VARCHAR(255) DEFAULT NULL, country_iso_code VARCHAR(3) DEFAULT NULL, latitude VARCHAR(32) DEFAULT NULL, longitude VARCHAR(32) DEFAULT NULL, gender VARCHAR(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, balance NUMERIC(10, 2) NOT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, subscriber TINYINT(1) NOT NULL, email VARCHAR(255) DEFAULT NULL, email_canonical VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, username_canonical VARCHAR(255) DEFAULT NULL, INDEX IDX_4C62E638DE12AB56 (created_by), UNIQUE INDEX UNIQ_4C62E638A75DB073 (active_membership), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_field (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, type VARCHAR(32) NOT NULL, required TINYINT(1) NOT NULL, show_on_contact_list TINYINT(1) DEFAULT \'0\' NOT NULL, sort INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_field_select_option (id INT AUTO_INCREMENT NOT NULL, contact_field_id INT DEFAULT NULL, option_name VARCHAR(255) NOT NULL, sort INT NOT NULL, INDEX IDX_671A0B61DE129B27 (contact_field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_field_value (id INT AUTO_INCREMENT NOT NULL, contact_field_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, field_value VARCHAR(255) DEFAULT NULL, INDEX IDX_587C7171DE129B27 (contact_field_id), INDEX IDX_587C7171E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file_attachment (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, file_name VARCHAR(128) NOT NULL, file_size INT NOT NULL, send_to_member INT NOT NULL, INDEX IDX_C0B7020D126F525E (item_id), INDEX IDX_C0B7020DE7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, inventory_item_id INT DEFAULT NULL, image_name VARCHAR(128) NOT NULL, INDEX IDX_C53D045F536BF4A2 (inventory_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, assigned_to INT DEFAULT NULL, current_location_id INT DEFAULT NULL, item_condition INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, sku VARCHAR(255) DEFAULT NULL, description VARCHAR(1024) DEFAULT NULL, keywords VARCHAR(1024) DEFAULT NULL, brand VARCHAR(1024) DEFAULT NULL, care_information VARCHAR(1024) DEFAULT NULL, component_information VARCHAR(1024) DEFAULT NULL, loan_fee NUMERIC(10, 2) DEFAULT NULL, max_loan_days INT DEFAULT NULL, is_active TINYINT(1) DEFAULT \'1\' NOT NULL, show_on_website TINYINT(1) DEFAULT \'1\' NOT NULL, serial_required TINYINT(1) NOT NULL, serial VARCHAR(64) DEFAULT NULL, note VARCHAR(128) DEFAULT NULL, price_cost NUMERIC(10, 2) DEFAULT NULL, price_sell NUMERIC(10, 2) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, short_url VARCHAR(64) DEFAULT NULL, item_type INT DEFAULT NULL, INDEX IDX_55BDEA30DE12AB56 (created_by), INDEX IDX_55BDEA3089EEAF91 (assigned_to), INDEX IDX_55BDEA30B8998A57 (current_location_id), INDEX IDX_55BDEA30B10C9EB3 (item_condition), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item_product_tag (inventory_item_id INT NOT NULL, product_tag_id INT NOT NULL, INDEX IDX_2F6598F5536BF4A2 (inventory_item_id), INDEX IDX_2F6598F5D8AE22B5 (product_tag_id), PRIMARY KEY(inventory_item_id, product_tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_location (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, barcode VARCHAR(32) DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_available TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_EAD4335A5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_condition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, sort INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_movement (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, inventory_item_id INT NOT NULL, inventory_location_id INT NOT NULL, loan_row_id INT DEFAULT NULL, assigned_to_contact_id INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_98D05D3CDE12AB56 (created_by), INDEX IDX_98D05D3C536BF4A2 (inventory_item_id), INDEX IDX_98D05D3C72BF1D41 (inventory_location_id), UNIQUE INDEX UNIQ_98D05D3C78219C8F (loan_row_id), INDEX IDX_98D05D3C7AA06E72 (assigned_to_contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_type (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE loan (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, created_by INT DEFAULT NULL, status VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, datetime_out DATETIME NOT NULL, datetime_in DATETIME NOT NULL, reference VARCHAR(32) DEFAULT NULL, total_fee NUMERIC(10, 2) NOT NULL, INDEX IDX_C5D30D03E7A1254A (contact_id), INDEX IDX_C5D30D03DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE loan_row (id INT AUTO_INCREMENT NOT NULL, loan_id INT DEFAULT NULL, inventory_item_id INT DEFAULT NULL, product_quantity INT NOT NULL, due_in_at DATETIME NOT NULL, due_out_at DATETIME DEFAULT NULL, checked_out_at DATETIME DEFAULT NULL, checked_in_at DATETIME DEFAULT NULL, fee NUMERIC(10, 2) NOT NULL, INDEX IDX_922D737FCE73868F (loan_id), INDEX IDX_922D737F536BF4A2 (inventory_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (id INT AUTO_INCREMENT NOT NULL, subscription_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, created_by INT DEFAULT NULL, price NUMERIC(10, 0) NOT NULL, created_at DATETIME NOT NULL, starts_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, status VARCHAR(32) NOT NULL, INDEX IDX_86FFD2859A1887DC (subscription_id), INDEX IDX_86FFD285E7A1254A (contact_id), INDEX IDX_86FFD285DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership_type (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, name VARCHAR(64) NOT NULL, price NUMERIC(10, 2) NOT NULL, duration INT NOT NULL, discount INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_F7E162E2DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, contact_id INT DEFAULT NULL, loan_id INT DEFAULT NULL, inventory_item_id INT DEFAULT NULL, created_at DATETIME NOT NULL, text VARCHAR(1024) DEFAULT NULL, INDEX IDX_CFBDFA14DE12AB56 (created_by), INDEX IDX_CFBDFA14E7A1254A (contact_id), INDEX IDX_CFBDFA14CE73868F (loan_id), INDEX IDX_CFBDFA14536BF4A2 (inventory_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, payment_method_id INT DEFAULT NULL, loan_id INT DEFAULT NULL, item_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, membership_id INT DEFAULT NULL, created_at DATETIME NOT NULL, type VARCHAR(36) NOT NULL, payment_date DATE NOT NULL, amount NUMERIC(10, 2) NOT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_6D28840DDE12AB56 (created_by), INDEX IDX_6D28840D5AA1164F (payment_method_id), INDEX IDX_6D28840DCE73868F (loan_id), INDEX IDX_6D28840D126F525E (item_id), INDEX IDX_6D28840DE7A1254A (contact_id), INDEX IDX_6D28840D1FB354CD (membership_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_field (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, type VARCHAR(32) NOT NULL, required TINYINT(1) NOT NULL, show_on_item_list TINYINT(1) DEFAULT \'0\' NOT NULL, show_on_website TINYINT(1) DEFAULT \'1\' NOT NULL, sort INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_field_select_option (id INT AUTO_INCREMENT NOT NULL, product_field_id INT DEFAULT NULL, option_name VARCHAR(255) NOT NULL, sort INT NOT NULL, INDEX IDX_63C603968F876D27 (product_field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_field_value (id INT AUTO_INCREMENT NOT NULL, product_field_id INT DEFAULT NULL, inventory_item_id INT DEFAULT NULL, field_value VARCHAR(255) DEFAULT NULL, INDEX IDX_9AFF50D08F876D27 (product_field_id), INDEX IDX_9AFF50D0536BF4A2 (inventory_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setting (setup_key VARCHAR(128) NOT NULL, setup_value VARCHAR(2056) NOT NULL, UNIQUE INDEX UNIQ_9F74B898EB89A0AB (setup_key), PRIMARY KEY(setup_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_search (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, text VARCHAR(1024) DEFAULT NULL, resultsReturned INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE waiting_list_item (id INT AUTO_INCREMENT NOT NULL, inventory_item_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, added_at DATETIME NOT NULL, removed_at DATETIME DEFAULT NULL, INDEX IDX_1846EEC6536BF4A2 (inventory_item_id), INDEX IDX_1846EEC6E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE child ADD CONSTRAINT FK_22B35429E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638A75DB073 FOREIGN KEY (active_membership) REFERENCES membership (id)');
        $this->addSql('ALTER TABLE contact_field_select_option ADD CONSTRAINT FK_671A0B61DE129B27 FOREIGN KEY (contact_field_id) REFERENCES contact_field (id)');
        $this->addSql('ALTER TABLE contact_field_value ADD CONSTRAINT FK_587C7171DE129B27 FOREIGN KEY (contact_field_id) REFERENCES contact_field (id)');
        $this->addSql('ALTER TABLE contact_field_value ADD CONSTRAINT FK_587C7171E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE file_attachment ADD CONSTRAINT FK_C0B7020D126F525E FOREIGN KEY (item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE file_attachment ADD CONSTRAINT FK_C0B7020DE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA30DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA3089EEAF91 FOREIGN KEY (assigned_to) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA30B8998A57 FOREIGN KEY (current_location_id) REFERENCES inventory_location (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA30B10C9EB3 FOREIGN KEY (item_condition) REFERENCES item_condition (id)');
        $this->addSql('ALTER TABLE inventory_item_product_tag ADD CONSTRAINT FK_2F6598F5536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_item_product_tag ADD CONSTRAINT FK_2F6598F5D8AE22B5 FOREIGN KEY (product_tag_id) REFERENCES product_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_movement ADD CONSTRAINT FK_98D05D3CDE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE item_movement ADD CONSTRAINT FK_98D05D3C536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE item_movement ADD CONSTRAINT FK_98D05D3C72BF1D41 FOREIGN KEY (inventory_location_id) REFERENCES inventory_location (id)');
        $this->addSql('ALTER TABLE item_movement ADD CONSTRAINT FK_98D05D3C78219C8F FOREIGN KEY (loan_row_id) REFERENCES loan_row (id)');
        $this->addSql('ALTER TABLE item_movement ADD CONSTRAINT FK_98D05D3C7AA06E72 FOREIGN KEY (assigned_to_contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE loan_row ADD CONSTRAINT FK_922D737FCE73868F FOREIGN KEY (loan_id) REFERENCES loan (id)');
        $this->addSql('ALTER TABLE loan_row ADD CONSTRAINT FK_922D737F536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD2859A1887DC FOREIGN KEY (subscription_id) REFERENCES membership_type (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE membership_type ADD CONSTRAINT FK_F7E162E2DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14DE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14CE73868F FOREIGN KEY (loan_id) REFERENCES loan (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DDE12AB56 FOREIGN KEY (created_by) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D5AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DCE73868F FOREIGN KEY (loan_id) REFERENCES loan (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D126F525E FOREIGN KEY (item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D1FB354CD FOREIGN KEY (membership_id) REFERENCES membership (id)');
        $this->addSql('ALTER TABLE product_field_select_option ADD CONSTRAINT FK_63C603968F876D27 FOREIGN KEY (product_field_id) REFERENCES product_field (id)');
        $this->addSql('ALTER TABLE product_field_value ADD CONSTRAINT FK_9AFF50D08F876D27 FOREIGN KEY (product_field_id) REFERENCES product_field (id)');
        $this->addSql('ALTER TABLE product_field_value ADD CONSTRAINT FK_9AFF50D0536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE waiting_list_item ADD CONSTRAINT FK_1846EEC6536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)');
        $this->addSql('ALTER TABLE waiting_list_item ADD CONSTRAINT FK_1846EEC6E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');

        // Sample data (also copied into fixture)
        $this->addSql("ALTER TABLE loan AUTO_INCREMENT = 1000");
        $this->addSql("ALTER TABLE inventory_item AUTO_INCREMENT = 1000");
        $this->addSql("INSERT INTO inventory_location VALUES (1,'On loan',NULL,1,0),(2,'In stock',NULL,1,1),(3,'Repair',NULL,1,0)");

        $this->addSql("INSERT INTO `membership_type` VALUES (1, null, 'Regular', 0.00, 365, 0, '2016-01-06 16:34:26')");
        $this->addSql("INSERT INTO `membership_type` VALUES (2, null, 'Temporary', 0.00, 14, 0, '2016-01-06 16:34:26')");

        $this->addSql("INSERT INTO `payment_method` VALUES (1,'Cash'), (2,'Credit/debit card'), (3,'Bank transfer')");
        $this->addSql("INSERT INTO `item_condition` VALUES (1,'A - As new',1), (2,'B - Fair',2), (3,'C - Poor',3)");

        $this->addSql("INSERT INTO `setting` VALUES
                ('default_checkin_location','2'),
                ('default_loan_fee','1.00'),
                ('default_loan_days','14'),
                ('industry','other'),
                ('site_allow_registration','1'),
                ('org_address','...'),
                ('org_country','GB'),
                ('org_currency','GBP'),
                ('org_email','email@demo.com'),
                ('org_lat','51.4425439'),
                ('org_long','-2.6152995'),
                ('org_name','Organisation name'),
                ('org_timezone','Europe/London');
                ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE child DROP FOREIGN KEY FK_22B35429E7A1254A');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638DE12AB56');
        $this->addSql('ALTER TABLE contact_field_value DROP FOREIGN KEY FK_587C7171E7A1254A');
        $this->addSql('ALTER TABLE file_attachment DROP FOREIGN KEY FK_C0B7020DE7A1254A');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA30DE12AB56');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA3089EEAF91');
        $this->addSql('ALTER TABLE item_movement DROP FOREIGN KEY FK_98D05D3CDE12AB56');
        $this->addSql('ALTER TABLE item_movement DROP FOREIGN KEY FK_98D05D3C7AA06E72');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D03E7A1254A');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D03DE12AB56');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285E7A1254A');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285DE12AB56');
        $this->addSql('ALTER TABLE membership_type DROP FOREIGN KEY FK_F7E162E2DE12AB56');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14DE12AB56');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14E7A1254A');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DDE12AB56');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DE7A1254A');
        $this->addSql('ALTER TABLE waiting_list_item DROP FOREIGN KEY FK_1846EEC6E7A1254A');
        $this->addSql('ALTER TABLE contact_field_select_option DROP FOREIGN KEY FK_671A0B61DE129B27');
        $this->addSql('ALTER TABLE contact_field_value DROP FOREIGN KEY FK_587C7171DE129B27');
        $this->addSql('ALTER TABLE file_attachment DROP FOREIGN KEY FK_C0B7020D126F525E');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F536BF4A2');
        $this->addSql('ALTER TABLE inventory_item_product_tag DROP FOREIGN KEY FK_2F6598F5536BF4A2');
        $this->addSql('ALTER TABLE item_movement DROP FOREIGN KEY FK_98D05D3C536BF4A2');
        $this->addSql('ALTER TABLE loan_row DROP FOREIGN KEY FK_922D737F536BF4A2');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14536BF4A2');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D126F525E');
        $this->addSql('ALTER TABLE product_field_value DROP FOREIGN KEY FK_9AFF50D0536BF4A2');
        $this->addSql('ALTER TABLE waiting_list_item DROP FOREIGN KEY FK_1846EEC6536BF4A2');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA30B8998A57');
        $this->addSql('ALTER TABLE item_movement DROP FOREIGN KEY FK_98D05D3C72BF1D41');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA30B10C9EB3');
        $this->addSql('ALTER TABLE loan_row DROP FOREIGN KEY FK_922D737FCE73868F');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14CE73868F');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DCE73868F');
        $this->addSql('ALTER TABLE item_movement DROP FOREIGN KEY FK_98D05D3C78219C8F');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638A75DB073');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D1FB354CD');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD2859A1887DC');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D5AA1164F');
        $this->addSql('ALTER TABLE product_field_select_option DROP FOREIGN KEY FK_63C603968F876D27');
        $this->addSql('ALTER TABLE product_field_value DROP FOREIGN KEY FK_9AFF50D08F876D27');
        $this->addSql('ALTER TABLE inventory_item_product_tag DROP FOREIGN KEY FK_2F6598F5D8AE22B5');
        $this->addSql('DROP TABLE child');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE contact_field');
        $this->addSql('DROP TABLE contact_field_select_option');
        $this->addSql('DROP TABLE contact_field_value');
        $this->addSql('DROP TABLE file_attachment');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE inventory_item_product_tag');
        $this->addSql('DROP TABLE inventory_location');
        $this->addSql('DROP TABLE item_condition');
        $this->addSql('DROP TABLE item_movement');
        $this->addSql('DROP TABLE item_type');
        $this->addSql('DROP TABLE loan');
        $this->addSql('DROP TABLE loan_row');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE membership_type');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE product_field');
        $this->addSql('DROP TABLE product_field_select_option');
        $this->addSql('DROP TABLE product_field_value');
        $this->addSql('DROP TABLE product_tag');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE site_search');
        $this->addSql('DROP TABLE waiting_list_item');
    }
}
