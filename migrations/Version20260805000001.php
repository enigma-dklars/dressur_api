<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table promotion_motif_refus (historique des motifs de refus)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE promotion_motif_refus (
            id INT AUTO_INCREMENT NOT NULL,
            promotion_id INT NOT NULL,
            motif LONGTEXT NOT NULL,
            date_refus DATETIME NOT NULL,
            INDEX IDX_PROMO_MOTIF_REFUS_PROMOTION (promotion_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE promotion_motif_refus
            ADD CONSTRAINT FK_PROMO_MOTIF_REFUS_PROMOTION
            FOREIGN KEY (promotion_id) REFERENCES promotion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion_motif_refus DROP FOREIGN KEY FK_PROMO_MOTIF_REFUS_PROMOTION');
        $this->addSql('DROP TABLE promotion_motif_refus');
    }
}
