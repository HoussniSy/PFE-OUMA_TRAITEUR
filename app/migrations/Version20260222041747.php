<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222041747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE service_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, color VARCHAR(7) NOT NULL, created_at DATETIME NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_FF3A42FC979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_item (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, unit VARCHAR(20) NOT NULL, current_quantity NUMERIC(10, 2) NOT NULL, minimum_quantity NUMERIC(10, 2) NOT NULL, unit_price NUMERIC(10, 2) DEFAULT NULL, last_restocked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_6017DDA979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE service_category ADD CONSTRAINT FK_FF3A42FC979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE stock_item ADD CONSTRAINT FK_6017DDA979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE client ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_C7440455979B1AD6 ON client (company_id)');
        $this->addSql('ALTER TABLE company ADD default_tax_rate NUMERIC(5, 2) NOT NULL, ADD default_payment_terms INT NOT NULL, ADD default_currency VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE document ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_D8698A76979B1AD6 ON document (company_id)');
        $this->addSql('ALTER TABLE document_item ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document_item ADD CONSTRAINT FK_B8AFA98D12469DE2 FOREIGN KEY (category_id) REFERENCES service_category (id)');
        $this->addSql('CREATE INDEX IDX_B8AFA98D12469DE2 ON document_item (category_id)');
        $this->addSql('ALTER TABLE user ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649979B1AD6 ON user (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_category DROP FOREIGN KEY FK_FF3A42FC979B1AD6');
        $this->addSql('ALTER TABLE stock_item DROP FOREIGN KEY FK_6017DDA979B1AD6');
        $this->addSql('DROP TABLE service_category');
        $this->addSql('DROP TABLE stock_item');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455979B1AD6');
        $this->addSql('DROP INDEX IDX_C7440455979B1AD6 ON client');
        $this->addSql('ALTER TABLE client DROP company_id');
        $this->addSql('ALTER TABLE company DROP default_tax_rate, DROP default_payment_terms, DROP default_currency');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76979B1AD6');
        $this->addSql('DROP INDEX IDX_D8698A76979B1AD6 ON document');
        $this->addSql('ALTER TABLE document DROP company_id');
        $this->addSql('ALTER TABLE document_item DROP FOREIGN KEY FK_B8AFA98D12469DE2');
        $this->addSql('DROP INDEX IDX_B8AFA98D12469DE2 ON document_item');
        $this->addSql('ALTER TABLE document_item DROP category_id');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('DROP INDEX IDX_8D93D649979B1AD6 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP company_id');
    }
}
