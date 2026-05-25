<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at and expired_at columns to story table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE story ADD updated_at DATETIME DEFAULT NULL, ADD expired_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE story DROP updated_at, DROP expired_at');
    }
}
