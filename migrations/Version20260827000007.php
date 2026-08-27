<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable comments to social network promotion orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promo_reseau ADD commentaires TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promo_reseau DROP COLUMN commentaires');
    }
}
