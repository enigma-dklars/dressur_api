<?php

namespace App\Services;

use App\Entity\DeveloperProfile;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\DeveloperProfileRepository;
use App\Repository\EnvRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class DeveloperAccessService
{
    public const CONDITIONS_VERSION = 'v1';

    public function __construct(
        private readonly EnvRepository $envRepository,
        private readonly DeveloperProfileRepository $developerProfileRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getMinimumRecharge(): int
    {
        return max(0, (int)($this->envRepository->find(1)?->getMontantRechargeInitialeDeveloppeur() ?? 0));
    }

    public function isActivationConfigured(): bool
    {
        return $this->getMinimumRecharge() > 0;
    }

    /**
     * @return array<string, bool|string>
     */
    public function getEligibility(User $user): array
    {
        return [
            'accountActive' => !($user->getBlocked() ?? false),
            'emailVerified' => $user->getMailIsVerified() ?? false,
            'phoneVerified' => $user->getTelIsVerified() ?? false,
            'profileComplete' => $this->isProfileComplete($user),
            'conditionsAccepted' => $user->getDeveloperProfile()?->getConditionsVersion() === self::CONDITIONS_VERSION,
        ];
    }

    public function isEligible(User $user): bool
    {
        foreach ($this->getEligibility($user) as $value) {
            if ($value !== true) {
                return false;
            }
        }

        return true;
    }

    public function isProfileComplete(User $user): bool
    {
        return trim((string)$user->getPseudo()) !== ''
            && trim((string)$user->getNom()) !== ''
            && trim((string)$user->getMail()) !== ''
            && trim((string)$user->getTel()) !== ''
            && $user->getPays() !== null;
    }

    public function getOrCreateProfile(User $user): DeveloperProfile
    {
        $profile = $user->getDeveloperProfile();
        if ($profile !== null) {
            return $profile;
        }

        $profile = (new DeveloperProfile())
            ->setUser($user)
            ->setStatus('pending');
        $user->setDeveloperProfile($profile);
        $this->entityManager->persist($profile);

        return $profile;
    }

    public function activateFromApprovedTransaction(Transaction $transaction): DeveloperProfile
    {
        $user = $transaction->getUser();
        if (!$user) {
            throw new \RuntimeException('La transaction développeur ne possède pas de compte utilisateur.');
        }

        $transactionInfo = $transaction->getAnnotherInfo() ?? [];
        $amount = (int)($transaction->getAmount() ?? 0);
        $minimum = $this->getMinimumRecharge();
        if ($minimum <= 0 || $amount < $minimum) {
            throw new \RuntimeException('Le minimum de recharge développeur n’est pas valide pour cette activation.');
        }

        $profile = $this->getOrCreateProfile($user);
        if ($profile->isActive()) {
            return $profile;
        }

        $profile
            ->setStatus('active')
            ->setConditionsVersion((string)($transactionInfo['conditionsVersion'] ?? self::CONDITIONS_VERSION))
            ->setConditionsAcceptedAt(new DateTime((string)($transactionInfo['conditionsAcceptedAt'] ?? 'now')))
            ->setActivationTransactionReference($transaction->getReference() ?: $transaction->getIdTransaction())
            ->setActivationAmount($amount)
            ->setActivatedAt(new DateTime())
            ->setSuspendedAt(null)
            ->setRevokedAt(null);

        return $profile;
    }
}
