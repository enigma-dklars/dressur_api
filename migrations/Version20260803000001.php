<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout du champ whatsapp_contact (nullable) sur la table promotion.
 */
final class Version20260803000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ whatsapp_contact nullable (VARCHAR 30) sur la table promotion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion ADD whatsapp_contact VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion DROP COLUMN whatsapp_contact');
    }
}
