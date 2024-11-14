<?php

namespace App\Repository;

use App\Entity\EnvPaiementApi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EnvPaiementApi>
 *
 * @method EnvPaiementApi|null find($id, $lockMode = null, $lockVersion = null)
 * @method EnvPaiementApi|null findOneBy(array $criteria, array $orderBy = null)
 * @method EnvPaiementApi[]    findAll()
 * @method EnvPaiementApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnvPaiementApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnvPaiementApi::class);
    }

//    /**
//     * @return EnvPaiementApi[] Returns an array of EnvPaiementApi objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EnvPaiementApi
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
