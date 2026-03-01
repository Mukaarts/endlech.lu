<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create restaurant_suggestion table for community restaurant submissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE restaurant_suggestion (id INT AUTO_INCREMENT NOT NULL, suggested_by_id INT DEFAULT NULL, name VARCHAR(150) NOT NULL, city VARCHAR(100) NOT NULL, cuisine VARCHAR(80) NOT NULL, emoji VARCHAR(10) NOT NULL, is_wheelchair_accessible TINYINT(1) NOT NULL, has_accessible_toilet TINYINT(1) NOT NULL, allows_assistance_dogs TINYINT(1) NOT NULL, has_bright_lighting TINYINT(1) NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, admin_note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_restaurant_suggestion_suggested_by (suggested_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE restaurant_suggestion ADD CONSTRAINT FK_restaurant_suggestion_suggested_by FOREIGN KEY (suggested_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion DROP FOREIGN KEY FK_restaurant_suggestion_suggested_by');
        $this->addSql('DROP TABLE restaurant_suggestion');
    }
}
