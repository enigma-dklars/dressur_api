<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prix_vendeur column to formule_promo_reseau (reseller price = 10% discount on normal coefficients)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formule_promo_reseau ADD prix_vendeur DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formule_promo_reseau DROP COLUMN prix_vendeur');
    }
}
