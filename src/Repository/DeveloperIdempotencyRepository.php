<?php

namespace App\Repository;

use App\Entity\DeveloperIdempotency;
use App\Entity\DeveloperProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeveloperIdempotencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeveloperIdempotency::class);
    }

    public function findValid(DeveloperProfile $profile, string $idempotencyKey): ?DeveloperIdempotency
    {
        $record = $this->findOneBy([
            'developerProfile' => $profile,
            'idempotencyKey' => $idempotencyKey,
        ]);

        if (!$record || $record->isExpired()) {
            return null;
        }

        return $record;
    }
}
