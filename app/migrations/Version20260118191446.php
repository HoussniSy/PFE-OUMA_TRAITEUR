<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260118191446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, name_arabic VARCHAR(255) DEFAULT NULL, registration_number VARCHAR(255) NOT NULL, nif VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, bank_name VARCHAR(255) NOT NULL, bank_account VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, number VARCHAR(255) NOT NULL, date DATE NOT NULL, location VARCHAR(255) DEFAULT NULL, total_ht NUMERIC(10, 2) NOT NULL, tax_rate NUMERIC(5, 2) NOT NULL, total_ttc NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, payment_terms INT NOT NULL, due_date DATE DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, client_id INT NOT NULL, UNIQUE INDEX UNIQ_D8698A7696901F54 (number), INDEX IDX_D8698A7619EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE document_item (id INT AUTO_INCREMENT NOT NULL, designation VARCHAR(255) NOT NULL, number_of_days INT NOT NULL, number_of_persons INT NOT NULL, number_of_services INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, position INT DEFAULT NULL, document_id INT NOT NULL, INDEX IDX_B8AFA98DC33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, recipient_email VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, scheduled_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, reminder_number INT DEFAULT NULL, document_id INT NOT NULL, INDEX IDX_BF5476CAC33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, date_paiement DATE NOT NULL, montant NUMERIC(10, 2) NOT NULL, mode_paiement VARCHAR(50) NOT NULL, statut_paiement VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, document_id INT NOT NULL, INDEX IDX_6D28840DC33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saved_filter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, page_type VARCHAR(50) NOT NULL, filters LONGTEXT NOT NULL, display_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_1BC22406A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) DEFAULT NULL, is_active TINYINT NOT NULL, is_verified TINYINT NOT NULL, email_verification_token VARCHAR(255) DEFAULT NULL, email_verification_token_expires_at DATETIME DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, phone VARCHAR(20) DEFAULT NULL, poste VARCHAR(100) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_audit_log (id INT AUTO_INCREMENT NOT NULL, target_user_email VARCHAR(180) NOT NULL, action VARCHAR(50) NOT NULL, details LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, performed_by_id INT NOT NULL, target_user_id INT DEFAULT NULL, INDEX IDX_F6014D112E65C292 (performed_by_id), INDEX IDX_F6014D116C066AFE (target_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE document_item ADD CONSTRAINT FK_B8AFA98DC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE saved_filter ADD CONSTRAINT FK_1BC22406A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_audit_log ADD CONSTRAINT FK_F6014D112E65C292 FOREIGN KEY (performed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_audit_log ADD CONSTRAINT FK_F6014D116C066AFE FOREIGN KEY (target_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A7619EB6921');
        $this->addSql('ALTER TABLE document_item DROP FOREIGN KEY FK_B8AFA98DC33F7837');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAC33F7837');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DC33F7837');
        $this->addSql('ALTER TABLE saved_filter DROP FOREIGN KEY FK_1BC22406A76ED395');
        $this->addSql('ALTER TABLE user_audit_log DROP FOREIGN KEY FK_F6014D112E65C292');
        $this->addSql('ALTER TABLE user_audit_log DROP FOREIGN KEY FK_F6014D116C066AFE');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE document_item');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE saved_filter');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_audit_log');
    }
}
