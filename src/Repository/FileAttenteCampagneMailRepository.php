<?php

namespace App\Repository;

use App\Entity\FileAttenteCampagneMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileAttenteCampagneMail>
 *
 * @method FileAttenteCampagneMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileAttenteCampagneMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileAttenteCampagneMail[]    findAll()
 * @method FileAttenteCampagneMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileAttenteCampagneMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileAttenteCampagneMail::class);
    }

    public function remove(FileAttenteCampagneMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
