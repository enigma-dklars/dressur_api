<?php

namespace App\Repository;

use App\Entity\FormulePromoAffaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormulePromoAffaire>
 *
 * @method FormulePromoAffaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormulePromoAffaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormulePromoAffaire[]    findAll()
 * @method FormulePromoAffaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormulePromoAffaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormulePromoAffaire::class);
    }

//    /**
//     * @return FormulePromoAffaire[] Returns an array of FormulePromoAffaire objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FormulePromoAffaire
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
