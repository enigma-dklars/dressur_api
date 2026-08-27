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
    public function findOrphanedForIdentity(?string $identityTel, ?string $identityMail): array
    {
        $query = $this->createQueryBuilder('restriction')
            ->andWhere('IDENTITY(restriction.user) IS NULL')
            ->andWhere('restriction.active = :active')
            ->andWhere('(restriction.expiresAt IS NULL OR restriction.expiresAt > :now)')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime());

        $identityExpressions = [];
        if ($identityTel !== null) {
            $identityExpressions[] = 'restriction.identityTel = :identityTel';
            $query->setParameter('identityTel', $identityTel);
        }
        if ($identityMail !== null) {
            $identityExpressions[] = 'restriction.identityMail = :identityMail';
            $query->setParameter('identityMail', $identityMail);
        }

        if ($identityExpressions === []) {
            return [];
        }

        return $query
            ->andWhere('(' . implode(' OR ', $identityExpressions) . ')')
            ->orderBy('restriction.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOrphanedForIdentityAndType(?string $identityTel, ?string $identityMail, string $type): ?UserRestriction
    {
        foreach ($this->findOrphanedForIdentity($identityTel, $identityMail) as $restriction) {
            if ($restriction->getType() === $type) {
                return $restriction;
            }
        }

        return null;
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
