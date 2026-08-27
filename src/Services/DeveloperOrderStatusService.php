<?php

namespace App\Services;

use App\Entity\PromoReseau;
use App\Utilities\ZefameApi;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class DeveloperOrderStatusService
{
    private const CACHE_SECONDS = 15;
    private const CACHE_TTL = 90;
    private const STATUS_LOCK_KEY = 48192731;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ZefameApi $zefameApi,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<PromoReseau> $orders
     * @return array{statuses: array<string, array<string, mixed>>, providerAvailable: bool}
     */
    public function getStatuses(array $orders): array
    {
        $statuses = [];
        $staleOrders = [];
        $now = time();

        foreach ($orders as $order) {
            $cached = $this->readCache($order);
            if ($cached !== null && (int)($cached['cachedAt'] ?? 0) + self::CACHE_SECONDS > $now) {
                $cached['statusSource'] = 'cache';
                $cached['nextCheckAt'] = date(DATE_ATOM, (int)$cached['cachedAt'] + self::CACHE_SECONDS);
                $cached['retryAfter'] = max(1, (int)$cached['cachedAt'] + self::CACHE_SECONDS - $now);
                $statuses[$order->getReference()] = $cached;
                continue;
            }

            if (in_array($order->getStatus(), [0, 3], true) || !$order->getIdZefame() || $order->getIdZefame() === '*****') {
                $statuses[$order->getReference()] = $this->localStatus($order, 'database', $now);
                continue;
            }

            $staleOrders[] = $order;
        }

        if ($staleOrders === []) {
            return ['statuses' => $statuses, 'providerAvailable' => true];
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $lockAcquired = (bool)$connection->executeQuery(
                'SELECT pg_try_advisory_xact_lock(' . self::STATUS_LOCK_KEY . ')'
            )->fetchOne();
            if (!$lockAcquired) {
                $connection->rollBack();
                foreach ($staleOrders as $order) {
                    $statuses[$order->getReference()] = $this->localStatus($order, 'database', $now);
                }

                return ['statuses' => $statuses, 'providerAvailable' => true];
            }

            $providerResult = $this->zefameApi->multiStatus(array_map(
                static fn (PromoReseau $order): string => (string)$order->getIdZefame(),
                $staleOrders
            ));
            if (!$providerResult || isset($providerResult->error)) {
                $connection->commit();
                foreach ($staleOrders as $order) {
                    $statuses[$order->getReference()] = $this->localStatus($order, 'database', $now);
                }

                return ['statuses' => $statuses, 'providerAvailable' => false];
            }
            $connection->commit();
        } catch (\Throwable) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            foreach ($staleOrders as $order) {
                $statuses[$order->getReference()] = $this->localStatus($order, 'database', $now);
            }

            return ['statuses' => $statuses, 'providerAvailable' => false];
        }

        foreach ($staleOrders as $order) {
            $providerStatus = $providerResult->{$order->getIdZefame()} ?? null;
            if ($providerStatus !== null && !isset($providerStatus->error)) {
                $this->applyProviderStatus($order, $providerStatus);
            }
            $status = $this->localStatus($order, 'provider', $now);
            $this->writeCache($order, $status);
            $statuses[$order->getReference()] = $status;
        }
        $this->entityManager->flush();

        return ['statuses' => $statuses, 'providerAvailable' => true];
    }

    /** @return array<string, mixed> */
    private function localStatus(PromoReseau $order, string $source, int $cachedAt): array
    {
        $status = match ($order->getStatus()) {
            0 => 'invalid_url',
            1 => 'pending',
            2 => 'in_progress',
            3 => 'completed',
            default => 'unknown',
        };

        return [
            'reference' => $order->getReference(),
            'status' => $status,
            'statusNumber' => $order->getStatus(),
            'statusSource' => $source,
            'providerCheckedAt' => $source === 'provider' ? date(DATE_ATOM, $cachedAt) : null,
            'cachedAt' => $cachedAt,
            'nextCheckAt' => date(DATE_ATOM, $cachedAt + self::CACHE_SECONDS),
            'retryAfter' => self::CACHE_SECONDS,
            'isFinal' => in_array($order->getStatus(), [0, 3], true),
            'quantity' => $order->getQteDemander(),
            'remaining' => $order->getCompteurRestant(),
            'startCount' => $order->getCompteurDebut(),
            'amount' => $order->getPrixFixer(),
        ];
    }

    private function applyProviderStatus(PromoReseau $order, object $providerStatus): void
    {
        $status = (string)($providerStatus->status ?? '');
        if ($status === 'In progress') {
            $order
                ->setStatus(2)
                ->setPrixZefame(isset($providerStatus->charge) ? (float)$providerStatus->charge : $order->getPrixZefame())
                ->setCompteurDebut(isset($providerStatus->start_count) ? (int)$providerStatus->start_count : $order->getCompteurDebut())
                ->setCompteurRestant(isset($providerStatus->remains) ? (int)$providerStatus->remains : $order->getCompteurRestant())
                ->setUpdatedAt(new DateTime());
        } elseif ($status === 'Completed') {
            $order->setStatus(3)->setCompteurRestant(0)->setUpdatedAt(new DateTime());
        } elseif ($status === 'Canceled') {
            $order->setStatus(0)->setUpdatedAt(new DateTime());
        }
    }

    /** @return array<string, mixed>|null */
    private function readCache(PromoReseau $order): ?array
    {
        $item = $this->cache->getItem($this->cacheKey($order));
        $value = $item->isHit() ? $item->get() : null;

        return is_array($value) ? $value : null;
    }

    /** @param array<string, mixed> $status */
    private function writeCache(PromoReseau $order, array $status): void
    {
        $item = $this->cache->getItem($this->cacheKey($order));
        $item->set($status)->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);
    }

    private function cacheKey(PromoReseau $order): string
    {
        return 'developer_order_status_' . hash('sha256', $order->getReference());
    }
}
