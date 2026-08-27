<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\PromoReseau;
use App\Services\DeveloperOrderStatusService;
use App\Utilities\ZefameApi;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class DeveloperOrderStatusServiceTest extends TestCase
{
    public function testFinalOrderUsesDatabaseStatusWithoutOpeningProviderConnection(): void
    {
        /** @var CacheItemPoolInterface&MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cache->expects(self::once())->method('getItem')->willReturn($cacheItem);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getConnection');
        $order = (new PromoReseau())
            ->setReference('pr_final_local')
            ->setStatus(3)
            ->setQteDemander(100)
            ->setPrixFixer(1500)
            ->setCompteurRestant(0);

        $result = $this->service($cache, $entityManager)->getStatuses([$order]);
        $status = $result['statuses']['pr_final_local'];

        self::assertTrue($result['providerAvailable']);
        self::assertSame('completed', $status['status']);
        self::assertSame('database', $status['statusSource']);
        self::assertTrue($status['isFinal']);
    }

    public function testFreshCacheIsReturnedWithoutCallingProviderOrDatabase(): void
    {
        /** @var CacheItemPoolInterface&MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn([
            'reference' => 'pr_cached',
            'status' => 'in_progress',
            'statusNumber' => 2,
            'statusSource' => 'provider',
            'cachedAt' => time(),
            'isFinal' => false,
        ]);
        $cache->expects(self::once())->method('getItem')->willReturn($cacheItem);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getConnection');
        $entityManager->expects(self::never())->method('flush');
        $order = (new PromoReseau())
            ->setReference('pr_cached')
            ->setStatus(2)
            ->setIdZefame('provider-123');

        $result = $this->service($cache, $entityManager)->getStatuses([$order]);
        $status = $result['statuses']['pr_cached'];

        self::assertTrue($result['providerAvailable']);
        self::assertSame('cache', $status['statusSource']);
        self::assertSame('in_progress', $status['status']);
        self::assertGreaterThanOrEqual(1, $status['retryAfter']);
    }

    private function service(CacheItemPoolInterface $cache, EntityManagerInterface $entityManager): DeveloperOrderStatusService
    {
        return new DeveloperOrderStatusService($cache, new ZefameApi(), $entityManager);
    }
}
