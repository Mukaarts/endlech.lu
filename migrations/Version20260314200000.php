<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ordering_option table for restaurant ordering platforms';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ordering_option (
            id INT AUTO_INCREMENT NOT NULL,
            restaurant_id INT NOT NULL,
            platform VARCHAR(20) NOT NULL,
            url VARCHAR(500) NOT NULL,
            INDEX IDX_ordering_option_restaurant (restaurant_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE ordering_option ADD CONSTRAINT FK_ordering_option_restaurant
            FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordering_option DROP FOREIGN KEY FK_ordering_option_restaurant');
        $this->addSql('DROP TABLE ordering_option');
    }
}
