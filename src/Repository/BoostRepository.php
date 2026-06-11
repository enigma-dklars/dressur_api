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
                INNER JOIN \`user\` u ON b.user_id = u.id
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
                INNER JOIN \`user\` u ON b.user_id = u.id
                WHERE u.mail IS NOT NULL AND u.mail != '' AND u.blocked = 0
                  AND b.date_exp BETWEEN :cutoff AND :now
                  AND NOT EXISTS (
                    SELECT 1 FROM boost b2 WHERE b2.user_id = u.id AND b2.date_exp > :now
                  )";

        return $conn->prepare($sql)->executeQuery(['cutoff' => $cutoff, 'now' => $now])->fetchAllAssociative();
    }

    public function getBoostAndUser($pays) //: array
    {
        return $this->createQueryBuilder('b')
            ->join("b.user", 'u')
            ->select('b boost')
            ->where('u.pays = :paysChoisie')
            ->setParameter('paysChoisie', $pays)
            ->getQuery()
            ->getResult()
        ;
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
}
