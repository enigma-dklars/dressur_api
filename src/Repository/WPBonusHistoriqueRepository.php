<?php

namespace App\Repository;

use App\Entity\WPBonusHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WPBonusHistorique>
 *
 * @method WPBonusHistorique|null find($id, $lockMode = null, $lockVersion = null)
 * @method WPBonusHistorique|null findOneBy(array $criteria, array $orderBy = null)
 * @method WPBonusHistorique[]    findAll()
 * @method WPBonusHistorique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WPBonusHistoriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WPBonusHistorique::class);
    }

    public function add(WPBonusHistorique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WPBonusHistorique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return WPBonusHistorique[] Returns an array of WPBonusHistorique objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WPBonusHistorique
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
