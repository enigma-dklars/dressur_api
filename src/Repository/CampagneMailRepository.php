<?php

namespace App\Repository;

use App\Entity\CampagneMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CampagneMail>
 *
 * @method CampagneMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method CampagneMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method CampagneMail[]    findAll()
 * @method CampagneMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampagneMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampagneMail::class);
    }

    public function save(CampagneMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CampagneMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
