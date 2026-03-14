<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add spoken_languages JSON column to restaurant table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE restaurant ADD spoken_languages JSON NOT NULL DEFAULT ('[]') COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP spoken_languages');
    }
}
