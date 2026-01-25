<?php

namespace App\Repository;

use App\Entity\HistoriqueProgrammeRecompense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueProgrammeRecompense>
 *
 * @method HistoriqueProgrammeRecompense|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriqueProgrammeRecompense|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriqueProgrammeRecompense[]    findAll()
 * @method HistoriqueProgrammeRecompense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriqueProgrammeRecompenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueProgrammeRecompense::class);
    }

//    /**
//     * @return HistoriqueProgrammeRecompense[] Returns an array of HistoriqueProgrammeRecompense objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?HistoriqueProgrammeRecompense
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
