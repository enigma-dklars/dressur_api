<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add add_page_actu boolean column to preference table (default true)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE preference ADD add_page_actu TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE preference DROP COLUMN add_page_actu');
    }
}
