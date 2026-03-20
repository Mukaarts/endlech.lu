<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add latitude, longitude and nearby_stops_note to restaurant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD latitude NUMERIC(10, 8) DEFAULT NULL, ADD longitude NUMERIC(11, 8) DEFAULT NULL, ADD nearby_stops_note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP latitude, DROP longitude, DROP nearby_stops_note');
    }
}
