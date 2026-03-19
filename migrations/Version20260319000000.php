<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add submitted_by_id to restaurant table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD submitted_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE restaurant ADD CONSTRAINT FK_EB95123F79F7D87D FOREIGN KEY (submitted_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_EB95123F79F7D87D ON restaurant (submitted_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP FOREIGN KEY FK_EB95123F79F7D87D');
        $this->addSql('DROP INDEX IDX_EB95123F79F7D87D ON restaurant');
        $this->addSql('ALTER TABLE restaurant DROP submitted_by_id');
    }
}
