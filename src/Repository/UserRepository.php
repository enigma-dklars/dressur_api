<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findAllPaginated(int $page = 1, int $limit = 20): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->getQuery();
        
        return $this->paginate($query, $page, $limit);
    }

    public function searchUsers(string $search, int $page = 1, int $limit = 20): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->where('u.pseudo LIKE :search')
            ->orWhere('u.nom LIKE :search')
            ->orWhere('u.mail LIKE :search')
            ->orWhere('u.tel LIKE :search')
            ->orWhere('u.uid LIKE :search')
            ->orWhere('u.id LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.id', 'DESC')
            ->getQuery();
        
        return $this->paginate($query, $page, $limit);
    }

    public function findAllIds(): array
    {
        return array_column(
            $this->createQueryBuilder('u')
                ->select('u.id')
                ->getQuery()
                ->getScalarResult(),
            'id'
        );
    }

    private function paginate($query, int $page = 1, int $limit = 20): Paginator
    {
        $paginator = new Paginator($query);
        
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        
        return $paginator;
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
