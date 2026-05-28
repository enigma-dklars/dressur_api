<?php

namespace App\Repository;

use App\Entity\DeletedDS;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeletedDS>
 *
 * @method DeletedDS|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeletedDS|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeletedDS[]    findAll()
 * @method DeletedDS[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeletedDSRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeletedDS::class);
    }

    public function add(DeletedDS $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DeletedDS $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
