<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable developer initial balance top-up amount to Env';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE env ADD montant_recharge_initiale_developpeur INT NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE env DROP COLUMN montant_recharge_initiale_developpeur');
    }
}
