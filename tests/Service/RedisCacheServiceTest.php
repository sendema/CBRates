<?php
namespace App\Tests\Service;

use App\Service\RedisCacheService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisCacheServiceTest extends TestCase
{
    private $redisClient;
    private $cacheItemPool;
    private $logger;
    private $redisCacheService;

    protected function setUp(): void
    {
        $this->redisClient = $this->createMock(Redis::class);
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->redisCacheService = new class($this->redisClient, $this->logger, $this->cacheItemPool) extends RedisCacheService {
            private CacheItemPoolInterface $testCacheItemPool;

            public function __construct(
                Redis $redis,
                ?LoggerInterface $logger,
                CacheItemPoolInterface $cacheItemPool
            ) {
                parent::__construct($redis, $logger);
                $this->testCacheItemPool = $cacheItemPool;
            }

            protected function getCacheItemPool(): CacheItemPoolInterface
            {
                return $this->testCacheItemPool;
            }
        };
    }

    public function testSetMethod()
    {
        $key = 'test_key';
        $value = ['data' => 'test_value'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('set')
            ->with($value);
        $cacheItem->expects($this->once())
            ->method('expiresAfter')
            ->with(3600);

        $this->cacheItemPool->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($cacheItem);
        $this->cacheItemPool->expects($this->once())
            ->method('save')
            ->with($cacheItem)
            ->willReturn(true);

        $result = $this->redisCacheService->set($key, $value);
        $this->assertTrue($result);
    }

    public function testDeleteMethod()
    {
        $key = 'test_key';

        $this->cacheItemPool->expects($this->once())
            ->method('deleteItem')
            ->with($key)
            ->willReturn(true);

        $result = $this->redisCacheService->delete($key);
        $this->assertTrue($result);
    }

    public function testClearMethod()
    {
        $this->cacheItemPool->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $result = $this->redisCacheService->clear();
        $this->assertTrue($result);
    }

    public function testErrorHandling()
    {
        $key = 'test_key';
        $expectedValue = ['data' => 'test_value'];

        $this->cacheItemPool->expects($this->once())
            ->method('getItem')
            ->willThrowException(new \Exception('Redis error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Redis cache error'),
                $this->isType('array')
            );

        $callback = fn() => $expectedValue;
        $result = $this->redisCacheService->get($key, $callback);
        $this->assertEquals($expectedValue, $result);
    }
}