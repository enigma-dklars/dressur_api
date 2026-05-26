<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add registerSource and lastLoginSource (web/mobile) to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD register_source VARCHAR(20) DEFAULT NULL, ADD last_login_source VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN register_source, DROP COLUMN last_login_source');
    }
}
