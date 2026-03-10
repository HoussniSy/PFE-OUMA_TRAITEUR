<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119135631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, available_variables LONGTEXT DEFAULT NULL, is_default TINYINT NOT NULL, is_active TINYINT NOT NULL, category VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9C0600CA77153098 (code), INDEX IDX_9C0600CAB03A8386 (created_by_id), INDEX IDX_9C0600CA896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE email_template_history (id INT AUTO_INCREMENT NOT NULL, version INT NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, action VARCHAR(20) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, template_id INT NOT NULL, modified_by_id INT NOT NULL, INDEX IDX_7A3BE3C55DA0FB8 (template_id), INDEX IDX_7A3BE3C599049ECE (modified_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CAB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE email_template_history ADD CONSTRAINT FK_7A3BE3C55DA0FB8 FOREIGN KEY (template_id) REFERENCES email_template (id)');
        $this->addSql('ALTER TABLE email_template_history ADD CONSTRAINT FK_7A3BE3C599049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CAB03A8386');
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CA896DBBDE');
        $this->addSql('ALTER TABLE email_template_history DROP FOREIGN KEY FK_7A3BE3C55DA0FB8');
        $this->addSql('ALTER TABLE email_template_history DROP FOREIGN KEY FK_7A3BE3C599049ECE');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP TABLE email_template_history');
    }
}
