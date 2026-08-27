<?php

namespace App\Services;

use App\Entity\DeveloperApiKey;
use App\Entity\DeveloperProfile;
use App\Repository\DeveloperApiKeyRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class DeveloperApiKeyService
{
    public const SCOPE_CATALOG_READ = 'catalog:read';
    public const SCOPE_BALANCE_READ = 'balance:read';
    public const SCOPE_ORDERS_READ = 'orders:read';
    public const SCOPE_ORDERS_WRITE = 'orders:write';
    public const SCOPE_STATUS_READ = 'status:read';

    public function __construct(
        private readonly DeveloperApiKeyRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<string> $scopes
     * @return array{key: DeveloperApiKey, secret: string}
     */
    public function createKey(DeveloperProfile $profile, string $label, array $scopes): array
    {
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > 120) {
            throw new \InvalidArgumentException('Le libellé de la clé est invalide.');
        }

        $allowedScopes = [
            self::SCOPE_CATALOG_READ,
            self::SCOPE_BALANCE_READ,
            self::SCOPE_ORDERS_READ,
            self::SCOPE_ORDERS_WRITE,
            self::SCOPE_STATUS_READ,
        ];
        $scopes = array_values(array_unique(array_intersect($allowedScopes, $scopes)));
        if ($scopes === []) {
            throw new \InvalidArgumentException('Au moins un scope valide est requis.');
        }

        $secret = bin2hex(random_bytes(32));
        $key = (new DeveloperApiKey())
            ->setDeveloperProfile($profile)
            ->setKeyId('drk_' . bin2hex(random_bytes(12)))
            ->setSecretHash(password_hash($secret, PASSWORD_DEFAULT))
            ->setSecretPrefix(substr($secret, 0, 8))
            ->setLabel($label)
            ->setScopes($scopes);

        $profile->addApiKey($key);
        $this->entityManager->persist($key);

        return ['key' => $key, 'secret' => $secret];
    }

    public function authenticate(Request $request): ?DeveloperApiKey
    {
        $authorization = trim((string)$request->headers->get('Authorization', ''));
        if (!preg_match('/^Bearer\s+(drk_[A-Za-z0-9]+)\.([a-f0-9]{64})$/', $authorization, $matches)) {
            return null;
        }

        $key = $this->repository->findActiveByKeyId($matches[1]);
        if (!$key || !password_verify($matches[2], $key->getSecretHash())) {
            return null;
        }

        $user = $key->getDeveloperProfile()?->getUser();
        if (!$user || ($user->getBlocked() ?? false)) {
            return null;
        }

        $key->setLastUsedAt(new DateTime());

        return $key;
    }

    public function hasScope(DeveloperApiKey $key, string $scope): bool
    {
        return $key->hasScope($scope);
    }

    public function revoke(DeveloperApiKey $key): void
    {
        $key->setRevokedAt(new DateTime());
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(DeveloperApiKey $key): array
    {
        return [
            'keyId' => $key->getKeyId(),
            'label' => $key->getLabel(),
            'secretPrefix' => $key->getSecretPrefix(),
            'scopes' => $key->getScopes(),
            'createdAt' => $key->getCreatedAt()->format(DATE_ATOM),
            'lastUsedAt' => $key->getLastUsedAt()?->format(DATE_ATOM),
            'expiresAt' => $key->getExpiresAt()?->format(DATE_ATOM),
            'revokedAt' => $key->getRevokedAt()?->format(DATE_ATOM),
        ];
    }
}
