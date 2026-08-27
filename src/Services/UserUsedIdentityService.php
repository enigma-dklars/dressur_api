<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\UserUsedIdentity;
use App\Repository\UserUsedIdentityRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserUsedIdentityService
{
    public function __construct(
        private UserUsedIdentityRepository $repository,
        private EntityManagerInterface $entityManager
    ) {
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

        return $value === '' || $value === '+' ? null : $value;
    }

    public function normalizeMail(?string $mail): ?string
    {
        $value = strtolower(trim((string) $mail));
        return $value === '' ? null : $value;
    }

    public function isUsedByAnother(string $type, string $value, ?string $currentValue = null): bool
    {
        $identity = $this->repository->findOneByTypeAndValue($type, $value);
        return $identity !== null && $identity->getValue() !== $currentValue;
    }

    public function rememberUser(User $user): void
    {
        $this->remember(UserUsedIdentity::TYPE_TEL, $this->normalizeTel($user->getTel()));
        $this->remember(UserUsedIdentity::TYPE_MAIL, $this->normalizeMail($user->getMail()));
    }

    private function remember(string $type, ?string $value): void
    {
        if ($value === null) {
            return;
        }

        $identity = $this->repository->findOneByTypeAndValue($type, $value);
        $now = new \DateTime();
        if ($identity === null) {
            $identity = (new UserUsedIdentity())
                ->setType($type)
                ->setValue($value)
                ->setFirstUsedAt($now);
            $this->entityManager->persist($identity);
        }
        $identity->setLastUsedAt($now);
    }

    public function countAll(): int
    {
        return $this->repository->countAll();
    }
}
