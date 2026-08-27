<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the shared user balance column to solde_dressur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN solde_programme_recompense TO solde_dressur');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN solde_dressur TO solde_programme_recompense');
    }
}
