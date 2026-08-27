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
        $this->addSql('ALTER TABLE formule_promo_reseau ADD commentaires_requis TINYINT(1) NOT NULL DEFAULT 0');

        // Backfill in PHP so the migration works with MySQL 5.7/8 and does not
        // depend on PostgreSQL recursive CTE or regex syntax.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, parent_id, titre FROM formule_promo_reseau'
        );
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['id']] = $row;
        }

        foreach ($rows as $row) {
            $formulaId = (int)$row['id'];
            $currentId = $formulaId;
            $visited = [];
            $requiresComments = false;

            while ($currentId > 0 && !isset($visited[$currentId]) && isset($byId[$currentId])) {
                $visited[$currentId] = true;
                $title = strtolower((string)($byId[$currentId]['titre'] ?? ''));
                if (preg_match('/commentaire|customis/u', $title) === 1) {
                    $requiresComments = true;
                    break;
                }
                $currentId = (int)($byId[$currentId]['parent_id'] ?? 0);
            }

            if ($requiresComments) {
                $this->addSql(
                    'UPDATE formule_promo_reseau SET commentaires_requis = 1 WHERE id = ?',
                    [$formulaId]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formule_promo_reseau DROP COLUMN commentaires_requis');
    }
}
