<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 *
 * @method Promotion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promotion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promotion[]    findAll()
 * @method Promotion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    public function save(Promotion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Promotion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // -------------------------------------------------------------------------
    // Option 1 — JOIN FETCH : résout le N+1 sur getUser()->getPreference()
    // Ces méthodes chargent Promotion + User + Preference en UNE seule requête SQL
    // au lieu de 1 + 2N requêtes avec le lazy loading de Doctrine.
    // -------------------------------------------------------------------------

    /**
     * Promotions actives (status=3, limited=true) avec User + Preference pré-chargés.
     * Utilisé par listePubliciteAffichageAuxUsers().
     *
     * @return Promotion[]
     */
    public function findActiveWithUserAndPreference(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('u', 'pref')
            ->innerJoin('p.user', 'u')
            ->leftJoin('u.preference', 'pref')
            ->where('p.status = :status')
            ->andWhere('p.limited = :limited')
            ->setParameter('status', 3)
            ->setParameter('limited', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Promotions VIP (limited=false) avec User + Preference pré-chargés.
     * Utilisé par la boucle VIP dans listePubliciteAffichageAuxUsers().
     *
     * @return Promotion[]
     */
    public function findVipWithUserAndPreference(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('u', 'pref')
            ->innerJoin('p.user', 'u')
            ->leftJoin('u.preference', 'pref')
            ->where('p.limited = :limited')
            ->setParameter('limited', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Promotions du Programme de Récompense (status=3, limited=true, inProgrammeRecompense=true)
     * avec User + Preference pré-chargés. Utilisé par listePromotionAffaireInProgrammeRecompense().
     *
     * @return Promotion[]
     */
    public function findRecompenseWithUserAndPreference(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('u', 'pref')
            ->innerJoin('p.user', 'u')
            ->leftJoin('u.preference', 'pref')
            ->where('p.status = :status')
            ->andWhere('p.limited = :limited')
            ->andWhere('p.inProgrammeRecompense = :inRecompense')
            ->setParameter('status', 3)
            ->setParameter('limited', true)
            ->setParameter('inRecompense', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs dont la dernière Promotion Affaire est terminée (status=4, dateExp dans les
     * $maxDaysAgo derniers jours) et sans Promotion active (status IN 1,2,3).
     */
    public function countUsersWithTerminatedPromoAndEmail(int $maxDaysAgo = 90): int
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND p.status = 4
                  AND p.date_exp IS NOT NULL AND p.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM promotion p2 WHERE p2.user_id = u.id AND p2.status IN (1, 2, 3)
                  )";

        return (int) $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchOne();
    }

    public function findUsersWithTerminatedPromoAndEmail(int $maxDaysAgo = 90): array
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT DISTINCT u.mail, u.pseudo
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND p.status = 4
                  AND p.date_exp IS NOT NULL AND p.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM promotion p2 WHERE p2.user_id = u.id AND p2.status IN (1, 2, 3)
                  )";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchAllAssociative();
    }

    public function countUsersWithTerminatedPromoAndTel(int $maxDaysAgo = 90): int
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND p.status = 4
                  AND p.date_exp IS NOT NULL AND p.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM promotion p2 WHERE p2.user_id = u.id AND p2.status IN (1, 2, 3)
                  )";

        return (int) $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchOne();
    }

    public function findUsersWithTerminatedPromoAndTel(int $maxDaysAgo = 90): array
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT DISTINCT u.tel, u.uid, u.pseudo
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND p.status = 4
                  AND p.date_exp IS NOT NULL AND p.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM promotion p2 WHERE p2.user_id = u.id AND p2.status IN (1, 2, 3)
                  )";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchAllAssociative();
    }

    public function findUsersWhoEverUsedPromoAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DISTINCT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Utilisateurs dont une Promotion Affaire est terminée et qui n’en ont pas d’active.
     */
    public function findUsersWithFinishedPromoAndNoActiveAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DISTINCT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE p.status = 4
                  AND u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND NOT EXISTS (
                      SELECT 1 FROM promotion p_active
                      WHERE p_active.user_id = u.id AND p_active.status IN (1, 2, 3)
                  )";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Utilisateurs ayant une Promotion Affaire refusée.
     */
    public function findUsersWithRefusedPromoAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DISTINCT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM promotion p
                INNER JOIN `user` u ON p.user_id = u.id
                WHERE p.status = 0
                  AND u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Retourne les promotions référençables pour le sitemap :
     * - statut 3 (accepter et en cours) OU 4 (terminer)
     * - isFakeVue != true
     *
     * @return Promotion[]
     */
    public function findForSitemap(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.isFakeVue IS NULL OR p.isFakeVue != :fakeVue')
            ->setParameter('statuses', [3, 4])
            ->setParameter('fakeVue', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getSourceCounts(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.source as source, COUNT(p.id) as cnt')
            ->groupBy('p.source')
            ->getQuery()
            ->getScalarResult();

        $result = ['web' => 0, 'mobile' => 0, 'none' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $key = isset($row['source']) && in_array($row['source'], ['web', 'mobile']) ? $row['source'] : 'none';
            $result[$key] += (int) $row['cnt'];
            $result['total'] += (int) $row['cnt'];
        }
        return $result;
    }



    public function countByDateRange(\DateTime $from, \DateTime $to): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT COUNT(id) FROM promotion WHERE date_debut >= :from AND date_debut < :to';
        return (int) $conn->prepare($sql)->executeQuery([
            'from' => $from->format('Y-m-d'),
            'to'   => $to->format('Y-m-d'),
        ])->fetchOne();
    }

    public function getDailyStats30Days(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $from = (new \DateTime('-29 days'))->format('Y-m-d');
        $to   = (new \DateTime())->format('Y-m-d');

        $sql = 'SELECT DATE(date_debut) AS day, COUNT(id) AS cnt
                FROM promotion
                WHERE DATE(date_debut) >= :from AND DATE(date_debut) <= :to
                GROUP BY day
                ORDER BY day ASC';

        $rows = $conn->prepare($sql)->executeQuery(['from' => $from, 'to' => $to])->fetchAllAssociative();

        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $result[(new \DateTime("-{$i} days"))->format('Y-m-d')] = 0;
        }
        foreach ($rows as $row) {
            if (isset($result[$row['day']])) {
                $result[$row['day']] = (int) $row['cnt'];
            }
        }
        return $result;
    }

    /**
     * Nombre total de promotions publiques (isFakeVue=false, status IN 3,4).
     * Utilisé par /actualite pour afficher le badge de comptage et piloter la pagination JS.
     */
    public function countAffaires(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isFakeVue = :fake')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('fake', false)
            ->setParameter('statuses', [3, 4])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Promotions publiques paginées avec User pré-chargé (JOIN FETCH).
     * Remplace findBy() + lazy load N×getUser() par 1 seule requête JOIN.
     * Ordrées par nombreDeVue DESC — ordre stable, cohérent entre pages.
     *
     * @return Promotion[]
     */
    public function findAffairesPaginated(int $offset, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('u')
            ->innerJoin('p.user', 'u')
            ->where('p.isFakeVue = :fake')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('fake', false)
            ->setParameter('statuses', [3, 4])
            ->orderBy('p.nombreDeVue', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Incrémente nombre_impression de 1 pour une liste d'IDs en une seule requête SQL.
     * Remplace le find() en boucle + flush ORM (N SELECT + N UPDATE → 1 UPDATE).
     * Bypass Doctrine intentionnel : les entités ne sont pas relues après cet appel dans /actu.
     *
     * @param int[] $ids
     */
    public function bulkIncrementImpression(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $conn->executeStatement(
            "UPDATE promotion SET nombre_impression = COALESCE(nombre_impression, 0) + 1 WHERE id IN ({$placeholders})",
            array_values($ids)
        );
    }

    public function findAllImageNames(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.image')
            ->where('p.image IS NOT NULL')
            ->andWhere('p.image != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getScalarResult();
        return array_column($rows, 'image');
    }

    /**
     * Comptages agrégés pour le dashboard admin.
     * Remplace 5 count(findBy(['status' => ...])) séparés qui chargeaient des entités complètes.
     * Avant : 5 requêtes × hydratation ORM. Après : 1 seule requête SELECT SUM.
     *
     * @return array{valid_promo_affaire:int, affaire_valider_sans_payer:int,
     *               encour_affaire:int, p_aff_recomp:int, p_aff_ds_statut:int}
     */
    public function getAdminPromoStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
            SELECT
                SUM(status = 1)                    AS valid_promo_affaire,
                SUM(status = 2)                    AS affaire_valider_sans_payer,
                SUM(status = 3)                    AS encour_affaire,
                SUM(in_programme_recompense = 1)   AS p_aff_recomp,
                SUM(publish_on_dressur_status = 1) AS p_aff_ds_statut
            FROM promotion
        ';
        return $conn->prepare($sql)->executeQuery()->fetchAssociative();
    }
}
