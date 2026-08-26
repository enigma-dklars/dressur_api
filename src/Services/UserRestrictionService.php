<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\UserRestriction;
use App\Repository\UserRestrictionRepository;

class UserRestrictionService
{
    public function __construct(private UserRestrictionRepository $repository)
    {
    }

    public function blocksFreeBoost(User $user): bool
    {
        $restriction = $this->repository->findOneForUserAndType(
            $user,
            UserRestriction::TYPE_BLOCK_FREE_BOOST
        );

        return $restriction?->isActive() === true;
    }

    public function getMinimumTransactionAmount(User $user): int
    {
        $restriction = $this->repository->findOneForUserAndType(
            $user,
            UserRestriction::TYPE_MINIMUM_TRANSACTION
        );

        if (!$restriction || !$restriction->isActive()) {
            return 0;
        }

        return max(0, (int) ($restriction->getMinimumTransactionAmount() ?? 0));
    }

    public function validateTransactionAmount(User $user, int $amount): ?string
    {
        $minimum = $this->getMinimumTransactionAmount($user);
        if ($minimum <= 0 || $amount >= $minimum) {
            return null;
        }

        return sprintf(
            'Cette transaction est inférieure au montant minimum de %s FCFA imposé à votre compte.%s',
            number_format($minimum, 0, ',', ' '),
            $this->formatReason($user, UserRestriction::TYPE_MINIMUM_TRANSACTION)
        );
    }

    public function freeBoostDenialMessage(User $user): string
    {
        return 'Le Boost Contact gratuit est temporairement indisponible pour votre compte.'
            . $this->formatReason($user, UserRestriction::TYPE_BLOCK_FREE_BOOST);
    }

    private function formatReason(User $user, string $type): string
    {
        $restriction = $this->repository->findOneForUserAndType($user, $type);
        $reason = trim((string) ($restriction?->getReason() ?? ''));

        return $reason !== '' ? ' Motif : ' . $reason : '';
    }
}
