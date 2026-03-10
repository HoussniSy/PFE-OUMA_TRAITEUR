<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222030911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dashboard_widget (id INT AUTO_INCREMENT NOT NULL, widget_type VARCHAR(50) NOT NULL, position INT NOT NULL, is_visible TINYINT NOT NULL, config JSON DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_6AC217EBA76ED395 (user_id), UNIQUE INDEX UNIQ_6AC217EBA76ED39514295C96 (user_id, widget_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE dashboard_widget ADD CONSTRAINT FK_6AC217EBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dashboard_widget DROP FOREIGN KEY FK_6AC217EBA76ED395');
        $this->addSql('DROP TABLE dashboard_widget');
    }
}
