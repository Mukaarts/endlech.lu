<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add opening_hour table and remove is_open column from restaurant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE opening_hour (id INT AUTO_INCREMENT NOT NULL, restaurant_id INT NOT NULL, day_of_week INT NOT NULL, open_time TIME DEFAULT NULL, close_time TIME DEFAULT NULL, is_closed TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX unique_restaurant_day (restaurant_id, day_of_week), INDEX IDX_969BD765B1E7706E (restaurant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE opening_hour ADD CONSTRAINT FK_969BD765B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE restaurant DROP is_open');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE opening_hour');
        $this->addSql('ALTER TABLE restaurant ADD is_open TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
