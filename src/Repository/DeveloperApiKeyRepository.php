<?php

namespace App\Repository;

use App\Entity\DeveloperApiKey;
use App\Entity\DeveloperProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeveloperApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeveloperApiKey::class);
    }

    public function findActiveByKeyId(string $keyId): ?DeveloperApiKey
    {
        $key = $this->findOneBy(['keyId' => $keyId, 'revokedAt' => null]);
        if (!$key || !$key->isActive()) {
            return null;
        }

        return $key;
    }

    /**
     * @return list<DeveloperApiKey>
     */
    public function findByProfile(DeveloperProfile $profile): array
    {
        return $this->findBy(['developerProfile' => $profile], ['id' => 'DESC']);
    }
}
