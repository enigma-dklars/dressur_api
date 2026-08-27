<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findAllPaginated(int $page = 1, int $limit = 20): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->getQuery();
        
        return $this->paginate($query, $page, $limit);
    }

    public function searchUsers(string $search, int $page = 1, int $limit = 20): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->where('u.pseudo LIKE :search')
            ->orWhere('u.nom LIKE :search')
            ->orWhere('u.mail LIKE :search')
            ->orWhere('u.tel LIKE :search')
            ->orWhere('u.uid LIKE :search')
            ->orWhere('u.id LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.id', 'DESC')
            ->getQuery();
        
        return $this->paginate($query, $page, $limit);
    }

    public function findAllIds(): array
    {
        return array_column(
            $this->createQueryBuilder('u')
                ->select('u.id')
                ->getQuery()
                ->getScalarResult(),
            'id'
        );
    }

    public function findCollectionCountsByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select(
                'u.id',
                'COUNT(DISTINCT b.id) AS boostsCount',
                'COUNT(DISTINCT p.id) AS promotionsCount',
                'COUNT(DISTINCT pr.id) AS promoReseausCount'
            )
            ->leftJoin('u.boosts', 'b')
            ->leftJoin('u.promotions', 'p')
            ->leftJoin('u.promoReseaus', 'pr')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $userIds)
            ->groupBy('u.id')
            ->getQuery()
            ->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = [
                'boosts'       => (int) $row['boostsCount'],
                'promotions'   => (int) $row['promotionsCount'],
                'promoReseaus' => (int) $row['promoReseausCount'],
            ];
        }
        return $result;
    }

    private function paginate($query, int $page = 1, int $limit = 20): Paginator
    {
        $paginator = new Paginator($query);
        
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        
        return $paginator;
    }

    public function findAllPaginatedFiltered(string $search, string $source, string $segment, int $page, int $limit): Paginator
    {
        $qb = $this->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('u.pseudo LIKE :search OR u.nom LIKE :search OR u.mail LIKE :search OR u.tel LIKE :search OR u.uid LIKE :search OR u.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($source === 'none') {
            $qb->andWhere('u.registerSource IS NULL');
        } elseif (in_array($source, ['web', 'mobile'], true)) {
            $qb->andWhere('u.registerSource = :source')
               ->setParameter('source', $source);
        }

        if ($segment === 'rewards') {
            $qb->andWhere('u.isInscritProgrammeRecompense = :rewards')
               ->setParameter('rewards', true);
        } elseif ($segment === 'sellers') {
            $qb->andWhere('u.vendeur = :seller')
               ->setParameter('seller', true);
        } elseif ($segment === 'partners') {
            $qb->andWhere('u.estPartenaire = :partner')
               ->setParameter('partner', true);
        }

        $qb->orderBy('u.id', 'DESC');

        return $this->paginate($qb->getQuery(), $page, $limit);
    }

    public function getRegisterSourceCounts(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.registerSource as source, COUNT(u.id) as cnt')
            ->groupBy('u.registerSource')
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

    public function findUsersWithTelAndWithoutLid(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.tel')
            ->where('u.tel IS NOT NULL')
            ->andWhere('u.tel != :empty')
            ->andWhere('u.lid IS NULL OR u.lid = :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Utilisateurs joignables n’ayant encore utilisé aucun service Dressur.
     *
     * @return array<int, array{user_id: int, tel: string, uid: string|null, pseudo: string|null, nom: string|null, mail: string|null}>
     */
    public function findUsersWithoutServiceAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM `user` u
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND NOT EXISTS (SELECT 1 FROM boost b WHERE b.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM promotion p WHERE p.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM promo_reseau pr WHERE pr.user_id = u.id)";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Nouveaux utilisateurs joignables sans aucun service utilisé depuis leur inscription.
     *
     * @return array<int, array{user_id: int, tel: string, uid: string|null, pseudo: string|null, nom: string|null, mail: string|null}>
     */
    public function findNewUsersWithoutServiceAndTelWithDetails(int $days = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $cutoff = (new \DateTime("-{$days} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM `user` u
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND u.created_at >= :cutoff
                  AND NOT EXISTS (SELECT 1 FROM boost b WHERE b.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM promotion p WHERE p.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM promo_reseau pr WHERE pr.user_id = u.id)";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff])->fetchAllAssociative();
    }

    /**
     * Utilisateurs joignables dont la dernière connexion remonte au moins à $days jours.
     *
     * @return array<int, array{user_id: int, tel: string, uid: string|null, pseudo: string|null, nom: string|null, mail: string|null}>
     */
    public function findInactiveUsersWithTelWithDetails(int $days = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $cutoff = (new \DateTime("-{$days} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM `user` u
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND u.last_login_to IS NOT NULL
                  AND u.last_login_to <= :cutoff";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff])->fetchAllAssociative();
    }

    /**
     * Utilisateurs ayant utilisé Boost Contact mais jamais Promotion Affaire.
     */
    public function findUsersWithBoostAndWithoutPromotionAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM `user` u
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND EXISTS (SELECT 1 FROM boost b WHERE b.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM promotion p WHERE p.user_id = u.id)";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Utilisateurs ayant utilisé Promotion Affaire mais jamais Boost Contact.
     */
    public function findUsersWithPromotionAndWithoutBoostAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM `user` u
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND EXISTS (SELECT 1 FROM promotion p WHERE p.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM boost b WHERE b.user_id = u.id)";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    /**
     * Utilisateurs ayant utilisé Promotion Réseaux Sociaux mais jamais Boost Contact.
     */
    public function findUsersWithPromoReseauAndWithoutBoostAndTelWithDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT u.id AS user_id, u.tel, u.uid, u.pseudo, u.nom, u.mail
                FROM `user` u
                WHERE u.tel IS NOT NULL AND u.tel != '' AND u.tel_is_verified = 1 AND u.blocked = 0
                  AND EXISTS (SELECT 1 FROM promo_reseau pr WHERE pr.user_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM boost b WHERE b.user_id = u.id)";

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

    public function countByDateRange(\DateTime $from, \DateTime $to): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT COUNT(id) FROM `user` WHERE created_at >= :from AND created_at < :to';
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

        $sql = 'SELECT DATE(created_at) AS day, COUNT(id) AS cnt
                FROM `user`
                WHERE DATE(created_at) >= :from AND DATE(created_at) <= :to
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
     * Comptages agrégés pour le dashboard admin.
     * Remplace 6 count(findAll/findBy) séparés qui chargeaient des entités complètes.
     * Avant : 6 requêtes × hydratation ORM. Après : 1 seule requête SELECT COUNT/SUM.
     *
     * @return array{nbr_user:int, users_prog_recomp:int, users_vendeur:int, users_partenaire:int, users_inactifs:int}
     */
    public function getAdminUserStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
            SELECT
                COUNT(*)                                            AS nbr_user,
                SUM(is_inscrit_programme_recompense = 1)           AS users_prog_recomp,
                SUM(vendeur = 1)                                   AS users_vendeur,
                SUM(est_partenaire = 1)                              AS users_partenaire,
                SUM(
                    NOT EXISTS (SELECT 1 FROM boost       WHERE boost.user_id       = u.id)
                AND NOT EXISTS (SELECT 1 FROM promotion   WHERE promotion.user_id   = u.id)
                AND NOT EXISTS (SELECT 1 FROM promo_reseau WHERE promo_reseau.user_id = u.id)
                )                                                  AS users_inactifs
            FROM `user` u
        ';
        return $conn->prepare($sql)->executeQuery()->fetchAssociative();
    }

    /**
     * Retourne la liste des utilisateurs sans aucun service (boost, promotion, promo_reseau),
     * triés par id DESC.
     *
     * @return array
     */
    public function findUsersWithoutService(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
            SELECT u.*
            FROM `user` u
            WHERE
                NOT EXISTS (SELECT 1 FROM boost        WHERE boost.user_id        = u.id)
            AND NOT EXISTS (SELECT 1 FROM promotion    WHERE promotion.user_id    = u.id)
            AND NOT EXISTS (SELECT 1 FROM promo_reseau WHERE promo_reseau.user_id = u.id)
            ORDER BY u.id DESC
        ';
        $rows = $conn->prepare($sql)->executeQuery()->fetchAllAssociative();

        // Ré-hydrater en entités User via l'ORM
        if (empty($rows)) {
            return [];
        }
        $ids = array_column($rows, 'id');
        return $this->findBy(['id' => $ids], ['id' => 'DESC']);
    }

    /**
     * Retourne tous les pays (indicatif téléphonique) avec le nombre d'utilisateurs,
     * triés du plus représenté au moins représenté.
     */
    public function getAllPaysByUserCount(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
            SELECT pays, COUNT(*) AS nbr
            FROM `user`
            WHERE pays IS NOT NULL AND pays != \'\'
            GROUP BY pays
            ORDER BY nbr DESC, pays ASC
        ';

        return $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
    }


}
