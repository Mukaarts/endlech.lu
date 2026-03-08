<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260308100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verification fields to restaurant table (isVerified, verifiedAt, verifiedBy)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD is_verified TINYINT(1) NOT NULL DEFAULT 0, ADD verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD verified_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE restaurant ADD CONSTRAINT FK_restaurant_verified_by FOREIGN KEY (verified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_restaurant_verified_by ON restaurant (verified_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP FOREIGN KEY FK_restaurant_verified_by');
        $this->addSql('DROP INDEX IDX_restaurant_verified_by ON restaurant');
        $this->addSql('ALTER TABLE restaurant DROP is_verified, DROP verified_at, DROP verified_by_id');
    }
}
