<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Env;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\DeveloperProfileRepository;
use App\Repository\EnvRepository;
use App\Services\DeveloperAccessService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeveloperAccessServiceTest extends TestCase
{
    /** @var EnvRepository&MockObject */
    private EnvRepository $envRepository;

    /** @var DeveloperProfileRepository&MockObject */
    private DeveloperProfileRepository $profileRepository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->envRepository = $this->createMock(EnvRepository::class);
        $this->profileRepository = $this->createMock(DeveloperProfileRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testMinimumRechargeIsClampedToZeroAndConfigurationDependsOnIt(): void
    {
        $this->envRepository->method('find')->with(1)->willReturn((new Env())->setMontantRechargeInitialeDeveloppeur(-100));
        $service = $this->service();

        self::assertSame(0, $service->getMinimumRecharge());
        self::assertFalse($service->isActivationConfigured());

        $positiveEnvRepository = $this->createMock(EnvRepository::class);
        $positiveEnvRepository->method('find')->with(1)->willReturn((new Env())->setMontantRechargeInitialeDeveloppeur(20000));
        $positiveService = new DeveloperAccessService($positiveEnvRepository, $this->profileRepository, $this->entityManager);

        self::assertSame(20000, $positiveService->getMinimumRecharge());
        self::assertTrue($positiveService->isActivationConfigured());
    }

    public function testEligibilityExplainsEveryMissingCondition(): void
    {
        $this->envRepository->method('find')->willReturn(null);
        $service = $this->service();
        $user = new User();

        self::assertSame([
            'accountActive' => true,
            'emailVerified' => false,
            'phoneVerified' => false,
            'profileComplete' => false,
            'conditionsAccepted' => false,
        ], $service->getEligibility($user));
        self::assertFalse($service->isEligible($user));
    }

    public function testCompleteProfileWithVerifiedContactAndAcceptedConditionsIsEligible(): void
    {
        $this->envRepository->method('find')->willReturn(null);
        $user = $this->completeUser();
        $profile = (new \App\Entity\DeveloperProfile())
            ->setUser($user)
            ->setConditionsVersion(DeveloperAccessService::CONDITIONS_VERSION);
        $user->setDeveloperProfile($profile);

        self::assertTrue($this->service()->isProfileComplete($user));
        self::assertTrue($this->service()->isEligible($user));
    }

    public function testGetOrCreateProfilePersistsOnlyWhenMissing(): void
    {
        $service = $this->service();
        $existingUser = new User();
        $existing = new \App\Entity\DeveloperProfile();
        $existingUser->setDeveloperProfile($existing);
        self::assertSame($existing, $service->getOrCreateProfile($existingUser));

        $newUser = new User();
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(\App\Entity\DeveloperProfile::class));
        $created = $service->getOrCreateProfile($newUser);

        self::assertSame($created, $newUser->getDeveloperProfile());
        self::assertSame('pending', $created->getStatus());
        self::assertSame($newUser, $created->getUser());
    }

    public function testApprovedTransactionActivatesAndStoresThePaidAmount(): void
    {
        $this->envRepository->method('find')->willReturn((new Env())->setMontantRechargeInitialeDeveloppeur(20000));
        $user = $this->completeUser();
        $transaction = (new Transaction())
            ->setUser($user)
            ->setAmount(25000)
            ->setReference('tx-developer-001')
            ->setAnnotherInfo([
                'conditionsVersion' => DeveloperAccessService::CONDITIONS_VERSION,
                'conditionsAcceptedAt' => '2026-08-27 10:00:00',
            ]);

        $profile = $this->service()->activateFromApprovedTransaction($transaction);

        self::assertSame('active', $profile->getStatus());
        self::assertSame(25000, $profile->getActivationAmount());
        self::assertSame('tx-developer-001', $profile->getActivationTransactionReference());
        self::assertSame(DeveloperAccessService::CONDITIONS_VERSION, $profile->getConditionsVersion());
        self::assertNotNull($profile->getActivatedAt());
    }

    public function testActivationRejectsPaymentBelowConfiguredMinimum(): void
    {
        $this->envRepository->method('find')->willReturn((new Env())->setMontantRechargeInitialeDeveloppeur(20000));
        $transaction = (new Transaction())->setUser(new User())->setAmount(19999);

        $this->expectException(\RuntimeException::class);
        $this->service()->activateFromApprovedTransaction($transaction);
    }

    private function service(): DeveloperAccessService
    {
        return new DeveloperAccessService($this->envRepository, $this->profileRepository, $this->entityManager);
    }

    private function completeUser(): User
    {
        return (new User())
            ->setPseudo('developpeur')
            ->setNom('Test')
            ->setMail('developer@example.com')
            ->setTel('+2250700000000')
            ->setPays(225)
            ->setMailIsVerified(true)
            ->setTelIsVerified(true);
    }
}
