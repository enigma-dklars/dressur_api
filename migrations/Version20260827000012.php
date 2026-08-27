<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stable public references to social network promotions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promo_reseau ADD reference VARCHAR(40) DEFAULT NULL');
        $this->addSql("UPDATE promo_reseau SET reference = 'pr_' || SUBSTRING(MD5(id::text || RANDOM()::text) FROM 1 FOR 24) WHERE reference IS NULL");
        $this->addSql('ALTER TABLE promo_reseau ALTER COLUMN reference SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROMO_RESEAU_REFERENCE ON promo_reseau (reference)');
        $this->addSql('ALTER TABLE developer_idempotency ADD order_reference VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE developer_idempotency ADD balance_after INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE developer_idempotency DROP COLUMN balance_after');
        $this->addSql('ALTER TABLE developer_idempotency DROP COLUMN order_reference');
        $this->addSql('DROP INDEX UNIQ_PROMO_RESEAU_REFERENCE');
        $this->addSql('ALTER TABLE promo_reseau DROP COLUMN reference');
    }
}
