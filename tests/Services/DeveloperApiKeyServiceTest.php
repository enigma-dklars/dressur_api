<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\DeveloperApiKey;
use App\Entity\DeveloperProfile;
use App\Entity\User;
use App\Repository\DeveloperApiKeyRepository;
use App\Services\DeveloperApiKeyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class DeveloperApiKeyServiceTest extends TestCase
{
    /** @var DeveloperApiKeyRepository&MockObject */
    private DeveloperApiKeyRepository $repository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private DeveloperApiKeyService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DeveloperApiKeyRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new DeveloperApiKeyService($this->repository, $this->entityManager);
    }

    public function testCreateKeyGeneratesAHashAndPublicMetadata(): void
    {
        $profile = $this->activeProfile();
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(DeveloperApiKey::class));

        $result = $this->service->createKey($profile, '  Mon intégration  ', [
            DeveloperApiKeyService::SCOPE_CATALOG_READ,
            DeveloperApiKeyService::SCOPE_CATALOG_READ,
            'unknown:scope',
        ]);

        $key = $result['key'];
        self::assertNotSame('', $result['secret']);
        self::assertStringStartsWith('drk_', $key->getKeyId());
        self::assertSame('Mon intégration', $key->getLabel());
        self::assertSame([DeveloperApiKeyService::SCOPE_CATALOG_READ], $key->getScopes());
        self::assertTrue(password_verify($result['secret'], $key->getSecretHash()));
        self::assertNotSame($result['secret'], $key->getSecretHash());
        self::assertSame($key, $profile->getApiKeys()->first());
    }

    public function testCreateKeyRejectsInvalidLabelAndScopes(): void
    {
        $profile = $this->activeProfile();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->createKey($profile, '   ', [DeveloperApiKeyService::SCOPE_CATALOG_READ]);
    }

    public function testCreateKeyRejectsWhenNoAllowedScopeIsProvided(): void
    {
        $profile = $this->activeProfile();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->createKey($profile, 'Integration', ['admin']);
    }

    public function testAuthenticateAcceptsAValidBearerTokenAndUpdatesLastUsedAt(): void
    {
        $profile = $this->activeProfile();
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(DeveloperApiKey::class));
        $created = $this->service->createKey($profile, 'Integration', [DeveloperApiKeyService::SCOPE_STATUS_READ]);
        $key = $created['key'];
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $key->getKeyId() . '.' . $created['secret'],
        ]);

        $this->repository->expects(self::once())
            ->method('findActiveByKeyId')
            ->with($key->getKeyId())
            ->willReturn($key);

        self::assertSame($key, $this->service->authenticate($request));
        self::assertNotNull($key->getLastUsedAt());
        self::assertTrue($this->service->hasScope($key, DeveloperApiKeyService::SCOPE_STATUS_READ));
        self::assertFalse($this->service->hasScope($key, DeveloperApiKeyService::SCOPE_ORDERS_WRITE));
    }

    public function testAuthenticateRejectsMalformedBearerWithoutQueryingRepository(): void
    {
        $this->repository->expects(self::never())->method('findActiveByKeyId');
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer drk_invalid.not-a-64-character-secret',
        ]);

        self::assertNull($this->service->authenticate($request));
    }

    public function testAuthenticateRejectsWrongSecret(): void
    {
        $profile = $this->activeProfile();
        $key = (new DeveloperApiKey())
            ->setDeveloperProfile($profile)
            ->setKeyId('drk_1234567890abcdef12345678')
            ->setSecretHash(password_hash(str_repeat('a', 64), PASSWORD_DEFAULT))
            ->setSecretPrefix('aaaaaaaa')
            ->setLabel('Integration')
            ->setScopes([DeveloperApiKeyService::SCOPE_CATALOG_READ]);
        $this->repository->method('findActiveByKeyId')->willReturn($key);
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $key->getKeyId() . '.' . str_repeat('b', 64),
        ]);

        self::assertNull($this->service->authenticate($request));
        self::assertNull($key->getLastUsedAt());
    }

    public function testAuthenticateRejectsBlockedAccount(): void
    {
        $user = (new User())->setBlocked(true);
        $profile = $this->activeProfile($user);
        $key = (new DeveloperApiKey())
            ->setDeveloperProfile($profile)
            ->setKeyId('drk_1234567890abcdef12345678')
            ->setSecretHash(password_hash(str_repeat('a', 64), PASSWORD_DEFAULT))
            ->setSecretPrefix('aaaaaaaa')
            ->setLabel('Integration')
            ->setScopes([DeveloperApiKeyService::SCOPE_CATALOG_READ]);
        $this->repository->method('findActiveByKeyId')->willReturn($key);
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $key->getKeyId() . '.' . str_repeat('a', 64),
        ]);

        self::assertNull($this->service->authenticate($request));
    }

    public function testRevokeFlushesAndMarksKeyAsRevoked(): void
    {
        $key = (new DeveloperApiKey())
            ->setDeveloperProfile($this->activeProfile())
            ->setKeyId('drk_1234567890abcdef12345678')
            ->setSecretHash('hash')
            ->setSecretPrefix('prefix')
            ->setLabel('Integration')
            ->setScopes([DeveloperApiKeyService::SCOPE_CATALOG_READ]);
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->revoke($key);

        self::assertNotNull($key->getRevokedAt());
    }

    private function activeProfile(?User $user = null): DeveloperProfile
    {
        $user ??= new User();
        return (new DeveloperProfile())
            ->setUser($user)
            ->setStatus('active');
    }
}
