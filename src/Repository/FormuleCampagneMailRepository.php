<?php

namespace App\Repository;

use App\Entity\FormuleCampagneMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormuleCampagneMail>
 *
 * @method FormuleCampagneMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormuleCampagneMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormuleCampagneMail[]    findAll()
 * @method FormuleCampagneMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormuleCampagneMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormuleCampagneMail::class);
    }

    public function save(FormuleCampagneMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormuleCampagneMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return FormuleCampagneMail[] Returns an array of FormuleCampagneMail objects
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

//    public function findOneBySomeField($value): ?FormuleCampagneMail
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
