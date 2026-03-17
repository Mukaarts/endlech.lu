<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314500000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sort_order column to restaurant_image and backfill based on uploaded_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_image ADD sort_order INT NOT NULL DEFAULT 0');

        // Bestehende Bilder: sortOrder basierend auf uploaded_at ASC pro Restaurant inkrementell setzen
        $this->addSql(<<<'SQL'
            UPDATE restaurant_image ri
            INNER JOIN (
                SELECT id,
                       ROW_NUMBER() OVER (PARTITION BY restaurant_id ORDER BY uploaded_at ASC) - 1 AS new_order
                FROM restaurant_image
            ) ranked ON ri.id = ranked.id
            SET ri.sort_order = ranked.new_order
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_image DROP COLUMN sort_order');
    }
}
