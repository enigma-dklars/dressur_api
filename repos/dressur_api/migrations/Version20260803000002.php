<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout du champ lecteur (TINYINT nullable) sur la table user.
 */
final class Version20260803000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ lecteur nullable (TINYINT 1) sur la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD lecteur TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN lecteur');
    }
}
