<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add explicit comments requirement to social network promotion formulas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formule_promo_reseau ADD commentaires_requis BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql("WITH RECURSIVE formule_hierarchie AS (
            SELECT id AS formule_id, id AS parent_id, titre
            FROM formule_promo_reseau
            UNION ALL
            SELECT fh.formule_id, parent.id AS parent_id, parent.titre
            FROM formule_hierarchie fh
            INNER JOIN formule_promo_reseau courant ON courant.id = fh.parent_id
            INNER JOIN formule_promo_reseau parent ON parent.id = courant.parent_id
            WHERE courant.parent_id IS NOT NULL
        )
        UPDATE formule_promo_reseau formule
        SET commentaires_requis = TRUE
        WHERE EXISTS (
            SELECT 1
            FROM formule_hierarchie fh
            WHERE fh.formule_id = formule.id
              AND lower(COALESCE(fh.titre, '')) ~ '(commentaire|customis)'
        )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formule_promo_reseau DROP COLUMN commentaires_requis');
    }
}
