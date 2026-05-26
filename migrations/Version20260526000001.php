<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source (web/mobile) and createdAt to promotion, boost, promo_reseau tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion ADD source VARCHAR(20) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE boost ADD source VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE promo_reseau ADD source VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion DROP COLUMN source, DROP COLUMN created_at');
        $this->addSql('ALTER TABLE boost DROP COLUMN source');
        $this->addSql('ALTER TABLE promo_reseau DROP COLUMN source');
    }
}
