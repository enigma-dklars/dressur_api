<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ia_active boolean column to env table (true = IA enabled, false = IA unavailable)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE env ADD ia_active TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE env DROP COLUMN ia_active');
    }
}
