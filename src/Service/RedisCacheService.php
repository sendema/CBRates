<?php

namespace App\Service;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

class RedisCacheService
{
    public function __construct(
        private TagAwareCacheInterface $cache
    ) {}

    public function get(string $key, callable $callback, array $tags = []): mixed
    {
        return $this->cache->get($key, function() use ($callback) {
            return $callback();
        });
    }

    public function invalidateTags(array $tags): bool
    {
        return $this->cache->invalidateTags($tags);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }
}