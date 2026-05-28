<?php

namespace App\Repository;

use App\Entity\ContactsUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactsUser>
 *
 * @method ContactsUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContactsUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContactsUser[]    findAll()
 * @method ContactsUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactsUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactsUser::class);
    }

    public function save(ContactsUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContactsUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
