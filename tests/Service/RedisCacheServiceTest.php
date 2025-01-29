<?php

namespace App\Tests\Service;

use App\Service\RedisCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class RedisCacheServiceTest extends TestCase
{
    private TagAwareCacheInterface $cache;
    private RedisCacheService $redisCacheService;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->redisCacheService = new RedisCacheService($this->cache);
    }

    public function testGetWithoutTags(): void
    {
        $key = 'test_key';
        $expectedValue = ['data' => 'test_value'];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($key),
                $this->isInstanceOf(\Closure::class)
            )
            ->willReturn($expectedValue);

        $result = $this->redisCacheService->get($key, fn() => $expectedValue);
        $this->assertEquals($expectedValue, $result);
    }

    public function testInvalidateTags(): void
    {
        $tags = ['tag1', 'tag2'];

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn(true);

        $result = $this->redisCacheService->invalidateTags($tags);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $key = 'test_key';

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($key)
            ->willReturn(true);

        $result = $this->redisCacheService->delete($key);
        $this->assertTrue($result);
    }
}