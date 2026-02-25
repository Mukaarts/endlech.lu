<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create restaurant table for accessibility-rated restaurants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE restaurant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, city VARCHAR(100) NOT NULL, cuisine VARCHAR(80) NOT NULL, emoji VARCHAR(10) NOT NULL, rating DOUBLE PRECISION DEFAULT NULL, is_open TINYINT(1) NOT NULL, is_wheelchair_accessible TINYINT(1) NOT NULL, has_accessible_toilet TINYINT(1) NOT NULL, allows_assistance_dogs TINYINT(1) NOT NULL, has_bright_lighting TINYINT(1) NOT NULL, accessibility_notes JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE restaurant');
    }
}
