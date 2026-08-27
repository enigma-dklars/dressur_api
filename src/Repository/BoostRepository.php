<?php

namespace App\Repository;

use App\Entity\Boost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boost>
 *
 * @method Boost|null find($id, $lockMode = null, $lockVersion = null)
 * @method Boost|null findOneBy(array $criteria, array $orderBy = null)
 * @method Boost[]    findAll()
 * @method Boost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boost::class);
    }

    public function add(Boost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Boost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Utilisateurs dont le dernier Boost a expiré dans les $maxDaysAgo derniers jours
     * et qui n'ont pas de Boost actif actuellement.
     */
    public function countUsersWithExpiredBoostAndEmail(int $maxDaysAgo = 90): int
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM boost b
                INNER JOIN `user` u ON b.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND b.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM boost b2 WHERE b2.user_id = u.id AND b2.date_exp > :now
                  )";

        return (int) $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchOne();
    }

    public function findUsersWithExpiredBoostAndEmail(int $maxDaysAgo = 90): array
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT DISTINCT u.mail, u.pseudo
                FROM boost b
                INNER JOIN `user` u ON b.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND b.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM boost b2 WHERE b2.user_id = u.id AND b2.date_exp > :now
                  )";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchAllAssociative();
    }

    public function findUsersWhoEverUsedBoostAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DISTINCT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM boost b
                INNER JOIN `user` u ON b.user_id = u.id
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Utilisateurs ayant utilisé un mode de boost, sans jamais utiliser l’autre.
     *
     * @return array<int, array{tel: string, uid: string|null, pseudo: string|null, nom: string|null, mail: string|null}>
     */
    public function findUsersWhoEverUsedOnlyBoostModeAndTelWithDetails(string $mode, string $excludedMode): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DISTINCT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM boost b
                INNER JOIN `user` u ON b.user_id = u.id
                WHERE b.`mode` = :mode
                  AND NOT EXISTS (
                      SELECT 1
                      FROM boost b_excluded
                      WHERE b_excluded.user_id = b.user_id
                        AND b_excluded.`mode` = :excluded_mode
                  )
                  AND u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0";

        return $conn->prepare($sql)->executeQuery([
            'mode' => $mode,
            'excluded_mode' => $excludedMode,
        ])->fetchAllAssociative();
    }

    /**
     * Utilisateurs dont le dernier Boost Contact remonte au moins à $days jours.
     *
     * @return array<int, array{tel: string, uid: string|null, pseudo: string|null, nom: string|null, mail: string|null}>
     */
    public function findUsersWhoseLastBoostIsAtLeastDaysAgoWithDetails(int $days = 7): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $cutoff = (new \DateTime("-{$days} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM boost b
                INNER JOIN `user` u ON b.user_id = u.id
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                GROUP BY u.id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                HAVING MAX(b.date_debut) <= :cutoff";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff])->fetchAllAssociative();
    }

    /**
     * Utilisateurs ayant déjà utilisé au moins un boost gratuit et un boost payant.
     *
     * @return array<int, array{tel: string, uid: string|null, pseudo: string|null, nom: string|null, mail: string|null}>
     */
    public function findUsersWhoEverUsedBoostWithBothModesAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DISTINCT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM boost b
                INNER JOIN `user` u ON b.user_id = u.id
                WHERE b.`mode` = 'Gratuit'
                  AND EXISTS (
                      SELECT 1
                      FROM boost b_payant
                      WHERE b_payant.user_id = b.user_id
                        AND b_payant.`mode` = 'Payant'
                  )
                  AND u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    // -------------------------------------------------------------------------
    // Option 1 — JOIN FETCH : résout le N+1 dans getAddDisponible()
    // -------------------------------------------------------------------------

    /**
     * Boosts actifs dont le user est dans l'un des $pays donnés,
     * avec User + Preference + Contact pré-chargés en une seule requête SQL.
     *
     * Avant : 1 SQL par pays + N lazy loads (User, Contact, Preference).
     * Après : 1 seule requête SQL, zéro lazy load.
     *
     * Les filtres SQL appliqués ici sont les mêmes que les vérifications PHP
     * du service — ils réduisent le volume hydraté ; les checks PHP restent
     * en place comme filet de sécurité.
     *
     * @param string[] $pays      Codes pays cibles (paysChoisies du user courant)
     * @param int      $excludeId ID du user courant (exclu des résultats)
     * @return Boost[]
     */
    public function findActiveBoostsForCountries(array $pays, int $excludeId): array
    {
        $now    = new \DateTime();
        $cutoff = (new \DateTime())->modify('-48 hours');

        return $this->createQueryBuilder('b')
            ->addSelect('u', 'pref', 'c')
            ->innerJoin('b.user', 'u')
            ->leftJoin('u.preference', 'pref')
            ->leftJoin('u.contact', 'c')
            ->where('u.pays IN (:pays)')
            ->andWhere('u.id != :excludeId')
            ->andWhere('u.lastLoginTo IS NOT NULL')
            ->andWhere('u.lastLoginTo >= :cutoff')
            ->andWhere('b.dateDebut <= :now')
            ->andWhere(
                '(b.typeBoost = :quota AND b.dateExp IS NULL) OR ' .
                '(b.typeBoost != :quota AND b.dateExp IS NOT NULL AND b.dateExp >= :now)'
            )
            ->setParameter('pays', $pays)
            ->setParameter('excludeId', $excludeId)
            ->setParameter('cutoff', $cutoff)
            ->setParameter('now', $now)
            ->setParameter('quota', 'quota')
            ->getQuery()
            ->getResult();
    }

    /**
     * Boosts actifs de tous pays confondus, avec User pré-chargé.
     *
     * Remplace findAll() dans le chemin admin de getAddDisponible().
     * Avant : toute la table + N lazy loads sur getUser().
     * Après : seulement les boosts actifs de users connectés dans les 48h.
     *
     * @return Boost[]
     */
    public function findActiveBoostsWithUser(): array
    {
        $now    = new \DateTime();
        $cutoff = (new \DateTime())->modify('-48 hours');

        return $this->createQueryBuilder('b')
            ->addSelect('u')
            ->innerJoin('b.user', 'u')
            ->where('u.lastLoginTo IS NOT NULL')
            ->andWhere('u.lastLoginTo >= :cutoff')
            ->andWhere('b.dateDebut <= :now')
            ->andWhere(
                '(b.typeBoost = :quota AND b.dateExp IS NULL) OR ' .
                '(b.typeBoost != :quota AND b.dateExp IS NOT NULL AND b.dateExp >= :now)'
            )
            ->setParameter('cutoff', $cutoff)
            ->setParameter('now', $now)
            ->setParameter('quota', 'quota')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les boosts triés par statut :
     *   1. En cours – Quota   (typeBoost = 'quota', dateExp IS NULL, déjà démarré)
     *   2. En cours – Durée   (typeBoost = 'date',  dateExp > now,   déjà démarré)
     *   3. Programmé          (dateDebut > now)
     *   4. Terminé            (tout le reste)
     * À statut égal, tri par id DESC (le plus récent en premier).
     */
    public function findAllOrderedByStatus(string $sourceFilter = '', string $statusFilter = ''): array
    {
        $qb = $this->createQueryBuilder('b')
            ->addSelect('
                CASE
                    WHEN b.dateDebut > CURRENT_TIMESTAMP() THEN 3
                    WHEN b.typeBoost = \'quota\' AND b.dateExp IS NULL THEN 1
                    WHEN b.typeBoost <> \'quota\' AND b.dateExp IS NOT NULL AND b.dateExp > CURRENT_TIMESTAMP() THEN 2
                    ELSE 4
                END AS HIDDEN statusOrder
            ')
            ->orderBy('statusOrder', 'ASC')
            ->addOrderBy('b.id', 'DESC');

        if ($sourceFilter === 'none') {
            $qb->andWhere('b.source IS NULL');
        } elseif (in_array($sourceFilter, ['web', 'mobile', 'admin'], true)) {
            $qb->andWhere('b.source = :source')
               ->setParameter('source', $sourceFilter);
        }

        if ($statusFilter === 'active') {
            $qb->andWhere('(b.dateDebut <= CURRENT_TIMESTAMP())')
               ->andWhere('(
                    (b.typeBoost = \'quota\' AND b.dateExp IS NULL)
                    OR (b.typeBoost <> \'quota\' AND b.dateExp IS NOT NULL AND b.dateExp > CURRENT_TIMESTAMP())
               )');
        } elseif ($statusFilter === 'scheduled') {
            $qb->andWhere('b.dateDebut > CURRENT_TIMESTAMP()');
        }

        return $qb->getQuery()->getResult();
    }

    public function getSourceCounts(): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('b.source as source, COUNT(b.id) as cnt')
            ->groupBy('b.source')
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



    /**
     * Retourne le premier boost actif d'un utilisateur, quel que soit son type :
     *  - quota : dateExp IS NULL (le quota n'est pas encore atteint)
     *  - date  : dateExp > maintenant (la période n'est pas encore expirée)
     */
    public function findBoostActif(\App\Entity\User $user): ?\App\Entity\Boost
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.dateExp IS NULL OR b.dateExp > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByDateRange(\DateTime $from, \DateTime $to): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT COUNT(id) FROM boost WHERE date_debut >= :from AND date_debut < :to';
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
                FROM boost
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
     * Retourne les stats journalières sur 30 jours pour un mode donné (Gratuit / Payant).
     */
    public function getDailyStats30DaysByMode(string $mode): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $from = (new \DateTime('-29 days'))->format('Y-m-d');
        $to   = (new \DateTime())->format('Y-m-d');

        $sql = 'SELECT DATE(date_debut) AS day, COUNT(id) AS cnt
                FROM boost
                WHERE DATE(date_debut) >= :from AND DATE(date_debut) <= :to
                  AND `mode` = :mode
                GROUP BY day
                ORDER BY day ASC';

        $rows = $conn->prepare($sql)->executeQuery(['from' => $from, 'to' => $to, 'mode' => $mode])->fetchAllAssociative();

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
}
