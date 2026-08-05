<?php

namespace App\Repository;

use App\Entity\AffiliationUsed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffiliationUsed>
 *
 * @method AffiliationUsed|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffiliationUsed|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffiliationUsed[]    findAll()
 * @method AffiliationUsed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffiliationUsedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffiliationUsed::class);
    }

    public function add(AffiliationUsed $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AffiliationUsed $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
