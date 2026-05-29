<?php

namespace App\Repository;

use App\Entity\LogBoiteMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBoiteMail>
 *
 * @method LogBoiteMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBoiteMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBoiteMail[]    findAll()
 * @method LogBoiteMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBoiteMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBoiteMail::class);
    }

    public function add(LogBoiteMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LogBoiteMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
