<?php
namespace App\Service;

use Redis;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class RedisCacheService
{
    private RedisAdapter $cache;
    private Redis $redis;
    private ?LoggerInterface $logger;

    public function __construct(
        Redis $redis,
        ?LoggerInterface $logger = null
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->cache = new RedisAdapter(
            $this->redis,
            'app',
            3600
        );
    }

    protected function getCacheItemPool(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    public function get(string $key, callable $callback = null, int $ttl = 3600): mixed
    {
        try {
            $cacheItemPool = $this->getCacheItemPool();
            $item = $cacheItemPool->getItem($key);

            if (!$item->isHit() && $callback !== null) {
                $value = $callback();
                $item->set($value);
                $item->expiresAfter($ttl);
                $cacheItemPool->save($item);
                return $value;
            }

            return $item->get();
        } catch (\Exception $e) {
            $this->logger?->error('Redis cache error', [
                'operation' => 'get',
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return $callback ? $callback() : null;
        }
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        try {
            $cacheItemPool = $this->getCacheItemPool();
            $item = $cacheItemPool->getItem($key);
            $item->set($value);
            $item->expiresAfter($ttl);

            return $cacheItemPool->save($item);
        } catch (\Exception $e) {
            $this->logger?->error('Redis cache error', [
                'operation' => 'set',
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $cacheItemPool = $this->getCacheItemPool();
            return $cacheItemPool->deleteItem($key);
        } catch (\Exception $e) {
            $this->logger?->error('Redis cache error', [
                'operation' => 'delete',
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            $cacheItemPool = $this->getCacheItemPool();
            return $cacheItemPool->hasItem($key);
        } catch (\Exception $e) {
            $this->logger?->error('Redis cache error', [
                'operation' => 'has',
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $cacheItemPool = $this->getCacheItemPool();
            return $cacheItemPool->clear();
        } catch (\Exception $e) {
            $this->logger?->error('Redis cache error', [
                'operation' => 'clear',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }
}