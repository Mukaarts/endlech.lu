<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment, dietary, language, contact and social media fields to restaurant_suggestion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion ADD has_changing_table TINYINT(1) NOT NULL DEFAULT 0, ADD has_disabled_parking TINYINT(1) NOT NULL DEFAULT 0, ADD accepts_cash TINYINT(1) NOT NULL DEFAULT 0, ADD accepts_card TINYINT(1) NOT NULL DEFAULT 0, ADD accepts_payconiq TINYINT(1) NOT NULL DEFAULT 0, ADD is_vegan TINYINT(1) NOT NULL DEFAULT 0, ADD is_vegetarian TINYINT(1) NOT NULL DEFAULT 0, ADD is_halal TINYINT(1) NOT NULL DEFAULT 0, ADD spoken_languages JSON NOT NULL, ADD phone VARCHAR(30) DEFAULT NULL, ADD email VARCHAR(180) DEFAULT NULL, ADD website VARCHAR(500) DEFAULT NULL, ADD instagram_url VARCHAR(500) DEFAULT NULL, ADD facebook_url VARCHAR(500) DEFAULT NULL, ADD tiktok_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion DROP has_changing_table, DROP has_disabled_parking, DROP accepts_cash, DROP accepts_card, DROP accepts_payconiq, DROP is_vegan, DROP is_vegetarian, DROP is_halal, DROP spoken_languages, DROP phone, DROP email, DROP website, DROP instagram_url, DROP facebook_url, DROP tiktok_url');
    }
}
