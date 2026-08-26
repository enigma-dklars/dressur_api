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
        return $this->findEffectiveRestriction($user, UserRestriction::TYPE_BLOCK_FREE_BOOST) !== null;
    }

    public function getMinimumTransactionAmount(User $user): int
    {
        $restriction = $this->findEffectiveRestriction($user, UserRestriction::TYPE_MINIMUM_TRANSACTION);
        if (!$restriction) {
            return 0;
        }

        return max(0, (int) ($restriction->getMinimumTransactionAmount() ?? 0));
    }

    public function validateTransactionAmount(User $user, int $amount): ?string
    {
        $restriction = $this->findEffectiveRestriction($user, UserRestriction::TYPE_MINIMUM_TRANSACTION);
        $minimum = max(0, (int) ($restriction?->getMinimumTransactionAmount() ?? 0));
        if (!$restriction || $minimum <= 0 || $amount >= $minimum) {
            return null;
        }

        return sprintf(
            'Compte restreint. Un montant minimum de %s FCFA est applicable à vos transactions payantes%s.',
            number_format($minimum, 0, ',', ' '),
            $this->formatUntil($restriction)
        );
    }

    public function freeBoostDenialMessage(User $user): string
    {
        $restriction = $this->findEffectiveRestriction($user, UserRestriction::TYPE_BLOCK_FREE_BOOST);
        return 'Compte restreint. Vous ne pouvez plus effectuer de Boost Contact gratuit'
            . $this->formatUntil($restriction) . '.';
    }

    private function findEffectiveRestriction(User $user, string $type): ?UserRestriction
    {
        $restriction = $this->repository->findOneForUserAndType($user, $type);
        if (!$restriction || !$restriction->isCurrentlyActive()) {
            return null;
        }

        return $restriction;
    }

    private function formatUntil(?UserRestriction $restriction): string
    {
        $expiresAt = $restriction?->getExpiresAt();
        if (!$expiresAt) {
            return ' jusqu’à la levée de la restriction';
        }

        return ' jusqu’au ' . $expiresAt->format('d/m/Y');
    }
}
