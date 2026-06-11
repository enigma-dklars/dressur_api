<?php

namespace App\Repository;

use App\Entity\PromoReseau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromoReseau>
 *
 * @method PromoReseau|null find($id, $lockMode = null, $lockVersion = null)
 * @method PromoReseau|null findOneBy(array $criteria, array $orderBy = null)
 * @method PromoReseau[]    findAll()
 * @method PromoReseau[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromoReseauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoReseau::class);
    }

    public function save(PromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Utilisateurs dont la dernière Promo Réseau est terminée (status=3, updatedAt dans les
     * $maxDaysAgo derniers jours) et sans commande active (status IN 1,2).
     */
    public function countUsersWithTerminatedPromoReseauAndEmail(int $maxDaysAgo = 90): int
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM promo_reseau pr
                INNER JOIN \`user\` u ON pr.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND pr.status = 3
                  AND pr.updated_at BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM promo_reseau pr2 WHERE pr2.user_id = u.id AND pr2.status IN (1, 2)
                  )";

        return (int) $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchOne();
    }

    public function findUsersWithTerminatedPromoReseauAndEmail(int $maxDaysAgo = 90): array
    {
        $conn   = $this->getEntityManager()->getConnection();
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $cutoff = (new \DateTime("-{$maxDaysAgo} days"))->format('Y-m-d H:i:s');

        $sql = "SELECT DISTINCT u.mail, u.pseudo
                FROM promo_reseau pr
                INNER JOIN \`user\` u ON pr.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND pr.status = 3
                  AND pr.updated_at BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM promo_reseau pr2 WHERE pr2.user_id = u.id AND pr2.status IN (1, 2)
                  )";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchAllAssociative();
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
        $sql  = 'SELECT COUNT(id) FROM promo_reseau WHERE created_at >= :from AND created_at < :to';
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
                FROM promo_reseau
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
}
