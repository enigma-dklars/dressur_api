<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les URLs sociales personnelles de la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN tiktok, DROP COLUMN instagram, DROP COLUMN facebook, DROP COLUMN youtube');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD tiktok TEXT DEFAULT NULL, ADD instagram TEXT DEFAULT NULL, ADD facebook TEXT DEFAULT NULL, ADD youtube TEXT DEFAULT NULL');
    }
}