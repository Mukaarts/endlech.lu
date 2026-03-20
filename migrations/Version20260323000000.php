<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cuisine table and restaurant_cuisine join table, migrate data from restaurant.cuisine column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cuisine (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_EC87E4645E237E06 (name), UNIQUE INDEX UNIQ_EC87E464989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE restaurant_cuisine (restaurant_id INT NOT NULL, cuisine_id INT NOT NULL, INDEX IDX_F5120B21B1E7706E (restaurant_id), INDEX IDX_F5120B21ED85880E (cuisine_id), PRIMARY KEY(restaurant_id, cuisine_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE restaurant_cuisine ADD CONSTRAINT FK_F5120B21B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE restaurant_cuisine ADD CONSTRAINT FK_F5120B21ED85880E FOREIGN KEY (cuisine_id) REFERENCES cuisine (id) ON DELETE CASCADE');
    }

    public function postUp(Schema $schema): void
    {
        $connection = $this->connection;

        // Migrate existing cuisine string values into cuisine entities
        $rows = $connection->fetchAllAssociative('SELECT id, cuisine FROM restaurant WHERE cuisine IS NOT NULL AND cuisine != \'\'');

        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
        $cuisineIds = [];

        foreach ($rows as $row) {
            $name = trim($row['cuisine']);
            if ($name === '') {
                continue;
            }

            $slug = strtolower((string) $slugger->slug($name));

            if (!isset($cuisineIds[$slug])) {
                $existing = $connection->fetchOne('SELECT id FROM cuisine WHERE slug = ?', [$slug]);
                if ($existing !== false) {
                    $cuisineIds[$slug] = (int) $existing;
                } else {
                    $connection->insert('cuisine', ['name' => $name, 'slug' => $slug]);
                    $cuisineIds[$slug] = (int) $connection->lastInsertId();
                }
            }

            $connection->insert('restaurant_cuisine', [
                'restaurant_id' => $row['id'],
                'cuisine_id' => $cuisineIds[$slug],
            ]);
        }

        // Drop the old column
        $connection->executeStatement('ALTER TABLE restaurant DROP COLUMN cuisine');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD cuisine VARCHAR(80) NOT NULL DEFAULT \'\'');

        // Restore data from join table
        $this->addSql('UPDATE restaurant r SET r.cuisine = (SELECT GROUP_CONCAT(c.name SEPARATOR \', \') FROM restaurant_cuisine rc JOIN cuisine c ON c.id = rc.cuisine_id WHERE rc.restaurant_id = r.id)');
        $this->addSql('UPDATE restaurant SET cuisine = \'\' WHERE cuisine IS NULL');

        $this->addSql('ALTER TABLE restaurant_cuisine DROP FOREIGN KEY FK_F5120B21B1E7706E');
        $this->addSql('ALTER TABLE restaurant_cuisine DROP FOREIGN KEY FK_F5120B21ED85880E');
        $this->addSql('DROP TABLE restaurant_cuisine');
        $this->addSql('DROP TABLE cuisine');
    }
}
