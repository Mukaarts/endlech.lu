<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260308110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Erstellt restaurant_image-Tabelle für Restaurant-Fotos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE restaurant_image (
            id INT AUTO_INCREMENT NOT NULL,
            restaurant_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) DEFAULT NULL,
            uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_B8EDBE54B1E7706E (restaurant_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE restaurant_image ADD CONSTRAINT FK_B8EDBE54B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_image DROP FOREIGN KEY FK_B8EDBE54B1E7706E');
        $this->addSql('DROP TABLE restaurant_image');
    }
}
