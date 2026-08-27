<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable vendor membership fee to Env';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE env ADD frais_adhesion_vendeur INT NOT NULL DEFAULT 2000");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE env DROP COLUMN frais_adhesion_vendeur');
    }
}
