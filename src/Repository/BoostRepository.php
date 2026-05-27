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

//    /**
//     * @return Boost[] Returns an array of Boost objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Boost
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
