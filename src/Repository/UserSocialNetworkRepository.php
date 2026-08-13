<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSocialNetwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSocialNetwork>
 */
class UserSocialNetworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSocialNetwork::class);
    }

    /**
     * @return list<UserSocialNetwork>
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['id' => 'ASC']);
    }

    public function findOneForUser(User $user, string $networkType): ?UserSocialNetwork
    {
        return $this->findOneBy([
            'user' => $user,
            'networkType' => $networkType,
        ]);
    }
}