<?php

namespace App\Repository;

use App\Entity\FormuleBoost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormuleBoost>
 *
 * @method FormuleBoost|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormuleBoost|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormuleBoost[]    findAll()
 * @method FormuleBoost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormuleBoostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormuleBoost::class);
    }

    public function add(FormuleBoost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormuleBoost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
