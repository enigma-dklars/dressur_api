<?php

namespace App\Repository;

use App\Entity\DSBonusHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DSBonusHistorique>
 *
 * @method DSBonusHistorique|null find($id, $lockMode = null, $lockVersion = null)
 * @method DSBonusHistorique|null findOneBy(array $criteria, array $orderBy = null)
 * @method DSBonusHistorique[]    findAll()
 * @method DSBonusHistorique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DSBonusHistoriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DSBonusHistorique::class);
    }

    public function add(DSBonusHistorique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DSBonusHistorique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return DSBonusHistorique[] Returns an array of DSBonusHistorique objects
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

//    public function findOneBySomeField($value): ?DSBonusHistorique
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
