<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222060647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sms_message (id INT AUTO_INCREMENT NOT NULL, recipient_phone VARCHAR(50) NOT NULL, recipient_name VARCHAR(255) DEFAULT NULL, message LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, segment_count INT DEFAULT NULL, sent_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, client_id INT DEFAULT NULL, INDEX IDX_46A7FBA519EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE whats_app_message (id INT AUTO_INCREMENT NOT NULL, recipient_phone VARCHAR(50) NOT NULL, recipient_name VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, message_type VARCHAR(50) NOT NULL, document_path VARCHAR(500) DEFAULT NULL, document_name VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, sent_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, client_id INT DEFAULT NULL, INDEX IDX_B62B27F119EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sms_message ADD CONSTRAINT FK_46A7FBA519EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE whats_app_message ADD CONSTRAINT FK_B62B27F119EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sms_message DROP FOREIGN KEY FK_46A7FBA519EB6921');
        $this->addSql('ALTER TABLE whats_app_message DROP FOREIGN KEY FK_B62B27F119EB6921');
        $this->addSql('DROP TABLE sms_message');
        $this->addSql('DROP TABLE whats_app_message');
    }
}
