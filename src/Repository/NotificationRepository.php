<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Retourne les notifications visibles par un utilisateur :
     * - les notifications qui lui sont destinées (n.user = $user)
     * - les notifications globales (n.user IS NULL)
     * Triées par date décroissante.
     *
     * @return Notification[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user OR n.user IS NULL')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteOlderThan(\DateTimeInterface $cutoff): int
    {
        return (int) $this->createQueryBuilder('n')
            ->delete()
            ->where('n.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
