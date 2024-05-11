<?php

namespace App\Repository;

use App\Entity\FormulePromoReseau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormulePromoReseau>
 *
 * @method FormulePromoReseau|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormulePromoReseau|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormulePromoReseau[]    findAll()
 * @method FormulePromoReseau[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormulePromoReseauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormulePromoReseau::class);
    }

    public function save(FormulePromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormulePromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return FormulePromoReseau[] Returns an array of FormulePromoReseau objects
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

//    public function findOneBySomeField($value): ?FormulePromoReseau
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
