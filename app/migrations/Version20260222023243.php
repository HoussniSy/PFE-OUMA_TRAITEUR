<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222023243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove dark mode field and dashboard widget table';
    }

    public function up(Schema $schema): void
    {
        // Drop dashboard_widget table if it exists
        if ($schema->hasTable('dashboard_widget')) {
            $this->addSql('ALTER TABLE dashboard_widget DROP FOREIGN KEY FK_dashboard_widget_user');
            $this->addSql('DROP TABLE dashboard_widget');
        }
        
        // Remove dark_mode column from user table if it exists
        if ($schema->getTable('`user`')->hasColumn('dark_mode')) {
            $this->addSql('ALTER TABLE `user` DROP dark_mode');
        }
    }

    public function down(Schema $schema): void
    {
        // Restore dashboard_widget table
        $this->addSql('CREATE TABLE dashboard_widget (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, widget_name VARCHAR(255) NOT NULL, position INT NOT NULL, is_visible TINYINT(1) NOT NULL, column_span INT NOT NULL, row_span INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX user_widget_unique (user_id, widget_name), INDEX IDX_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Restore dark_mode column
        $this->addSql('ALTER TABLE `user` ADD dark_mode TINYINT(1) NOT NULL DEFAULT 0');
        
        // Restore foreign key
        $this->addSql('ALTER TABLE dashboard_widget ADD CONSTRAINT FK_dashboard_widget_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
