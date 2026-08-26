<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserRestriction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRestriction>
 */
class UserRestrictionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRestriction::class);
    }

    public function findOneForUserAndType(User $user, string $type): ?UserRestriction
    {
        return $this->findOneBy(['user' => $user, 'type' => $type]);
    }

    /**
     * @return UserRestriction[]
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['updatedAt' => 'DESC']);
    }

    /**
     * @return UserRestriction[]
     */
    public function findActiveForUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'active' => true], ['updatedAt' => 'DESC']);
    }
}
