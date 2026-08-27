<?php

namespace App\Repository;

use App\Entity\UserUsedIdentity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserUsedIdentity>
 */
class UserUsedIdentityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserUsedIdentity::class);
    }

    public function findOneByTypeAndValue(string $type, string $value): ?UserUsedIdentity
    {
        return $this->findOneBy(['type' => $type, 'value' => $value]);
    }

    public function isUsed(string $type, string $value, ?string $currentValue = null): bool
    {
        $identity = $this->findOneByTypeAndValue($type, $value);
        return $identity !== null && ($currentValue === null || $currentValue !== $value);
    }

    /**
     * @return UserUsedIdentity[]
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['type' => 'ASC', 'lastUsedAt' => 'DESC']);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('identity')
            ->select('COUNT(identity.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
