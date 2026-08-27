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

    /**
     * Capture les identités actuellement connues de l’utilisateur sur la restriction.
     * Les valeurs sont conservées même si le compte est ensuite purgé.
     */
    public function captureIdentity(UserRestriction $restriction, User $user): void
    {
        $restriction
            ->setIdentityTel($this->normalizeTel($user->getTel()))
            ->setIdentityMail($this->normalizeMail($user->getMail()));
    }

    /**
     * Rattache au compte les restrictions orphelines dont une identité vérifiée
     * correspond. Chaque type ne peut être rattaché qu’une seule fois.
     */
    public function restoreForUser(User $user): int
    {
        $identityTel = $user->getTelIsVerified() === true ? $this->normalizeTel($user->getTel()) : null;
        $identityMail = $user->getMailIsVerified() === true ? $this->normalizeMail($user->getMail()) : null;
        if ($identityTel === null && $identityMail === null) {
            return 0;
        }

        $attachedTypes = [];
        $restored = 0;
        foreach ($this->repository->findOrphanedForIdentity($identityTel, $identityMail) as $restriction) {
            $type = $restriction->getType();
            if ($type === null || isset($attachedTypes[$type]) || $this->repository->findOneForUserAndType($user, $type)) {
                continue;
            }

            $restriction
                ->setUser($user)
                ->setUpdatedAt(new \DateTime());
            $attachedTypes[$type] = true;
            $restored++;
        }

        return $restored;
    }

    public function normalizeTel(?string $tel): ?string
    {
        $value = trim((string) $tel);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', $value) ?? '';
        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }
        if ($value === '' || $value === '+') {
            return null;
        }

        return $value;
    }

    public function normalizeMail(?string $mail): ?string
    {
        $value = strtolower(trim((string) $mail));
        return $value === '' ? null : $value;
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
