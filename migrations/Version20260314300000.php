<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Kontaktdaten und Social-Media-Links zur Restaurant-Tabelle hinzufügen';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD phone VARCHAR(30) DEFAULT NULL, ADD email VARCHAR(180) DEFAULT NULL, ADD website VARCHAR(500) DEFAULT NULL, ADD instagram_url VARCHAR(500) DEFAULT NULL, ADD facebook_url VARCHAR(500) DEFAULT NULL, ADD tiktok_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP phone, DROP email, DROP website, DROP instagram_url, DROP facebook_url, DROP tiktok_url');
    }
}
