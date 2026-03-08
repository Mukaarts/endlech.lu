<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260308000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment method fields to restaurant table (acceptsCash, acceptsCard, acceptsPayconiq)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD accepts_cash TINYINT(1) NOT NULL DEFAULT 0, ADD accepts_card TINYINT(1) NOT NULL DEFAULT 0, ADD accepts_payconiq TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP accepts_cash, DROP accepts_card, DROP accepts_payconiq');
    }
}
