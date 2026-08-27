<?php

namespace App\Services;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

class DeveloperApiProtectionService
{
    public function __construct(private readonly CacheItemPoolInterface $cache)
    {
    }

    /**
     * @param list<array{key: string, limit: int, window: int}> $limits
     * @return array{allowed: bool, retryAfter: int}
     */
    public function consume(Request $request, string $keyId, array $limits): array
    {
        $retryAfter = 0;
        foreach ($limits as $limit) {
            $cacheKey = 'developer_rate_' . hash('sha256', $limit['key']);
            $item = $this->cache->getItem($cacheKey);
            $now = time();
            $bucket = $item->isHit() && is_array($item->get()) ? $item->get() : null;
            if (!$bucket || (int)($bucket['startedAt'] ?? 0) + $limit['window'] <= $now) {
                $bucket = ['startedAt' => $now, 'count' => 0];
            }

            if ((int)$bucket['count'] >= $limit['limit']) {
                $retryAfter = max($retryAfter, max(1, (int)$bucket['startedAt'] + $limit['window'] - $now));
                continue;
            }

            $bucket['count']++;
            $item->set($bucket)->expiresAfter($limit['window']);
            $this->cache->save($item);
        }

        return ['allowed' => $retryAfter === 0, 'retryAfter' => $retryAfter];
    }

    /**
     * @return list<array{key: string, limit: int, window: int}>
     */
    public function statusLimits(Request $request, string $keyId, int $accountId): array
    {
        $ip = $request->getClientIp() ?: 'unknown';

        return [
            ['key' => 'key:' . $keyId, 'limit' => 30, 'window' => 60],
            ['key' => 'account:' . $accountId, 'limit' => 60, 'window' => 60],
            ['key' => 'ip:' . $ip, 'limit' => 120, 'window' => 60],
        ];
    }
}
