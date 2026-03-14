<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dietary option columns (is_vegan, is_vegetarian, is_halal) to restaurant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD is_vegan TINYINT(1) NOT NULL DEFAULT 0, ADD is_vegetarian TINYINT(1) NOT NULL DEFAULT 0, ADD is_halal TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP is_vegan, DROP is_vegetarian, DROP is_halal');
    }
}
