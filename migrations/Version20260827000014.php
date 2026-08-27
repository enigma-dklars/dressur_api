<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add administrable Zefame API key to Env';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE env ADD zefame_api_key VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE env DROP zefame_api_key');
    }
}
