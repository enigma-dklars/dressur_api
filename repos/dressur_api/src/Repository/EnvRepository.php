<?php

namespace App\Repository;

use App\Entity\Env;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Env>
 *
 * @method Env|null find($id, $lockMode = null, $lockVersion = null)
 * @method Env|null findOneBy(array $criteria, array $orderBy = null)
 * @method Env[]    findAll()
 * @method Env[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Env::class);
    }

    public function add(Env $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Env $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
