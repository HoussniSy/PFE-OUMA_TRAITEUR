<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222042954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD smtp_host VARCHAR(255) DEFAULT NULL, ADD smtp_port INT DEFAULT NULL, ADD smtp_username VARCHAR(255) DEFAULT NULL, ADD smtp_password LONGTEXT DEFAULT NULL, ADD smtp_encryption VARCHAR(10) DEFAULT NULL, ADD email_configured TINYINT NOT NULL, ADD email_configured_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP smtp_host, DROP smtp_port, DROP smtp_username, DROP smtp_password, DROP smtp_encryption, DROP email_configured, DROP email_configured_at');
    }
}
