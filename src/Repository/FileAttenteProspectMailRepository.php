<?php

namespace App\Repository;

use App\Entity\FileAttenteProspectMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileAttenteProspectMail>
 *
 * @method FileAttenteProspectMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileAttenteProspectMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileAttenteProspectMail[]    findAll()
 * @method FileAttenteProspectMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileAttenteProspectMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileAttenteProspectMail::class);
    }

    public function add(FileAttenteProspectMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FileAttenteProspectMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
