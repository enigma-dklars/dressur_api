<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expiration date to user restrictions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_restriction ADD expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_restriction DROP expires_at');
    }
}
