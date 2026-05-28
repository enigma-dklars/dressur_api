<?php

namespace App\Repository;

use App\Entity\VerifMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerifMail>
 *
 * @method VerifMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerifMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerifMail[]    findAll()
 * @method VerifMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerifMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerifMail::class);
    }

    public function add(VerifMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VerifMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
