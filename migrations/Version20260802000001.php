<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add boost_facebook and montant_boost_facebook columns to promotion table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion ADD boost_facebook BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE promotion ADD montant_boost_facebook INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion DROP COLUMN montant_boost_facebook');
        $this->addSql('ALTER TABLE promotion DROP COLUMN boost_facebook');
    }
}
