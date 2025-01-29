<?php

namespace App\Tests\Service\CBR;

use App\Client\CBRClient;
use App\Service\CBR\CBRService;
use App\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CBRServiceTest extends TestCase
{
    private CBRClient $cbrClient;
    private EntityManagerInterface $entityManager;
    private TagAwareCacheInterface $cache;
    private LoggerInterface $logger;
    private EntityRepository $repository;
    private CBRService $cbrService;

    protected function setUp(): void
    {
        $this->cbrClient = $this->createMock(CBRClient::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(ExchangeRate::class)
            ->willReturn($this->repository);

        $this->cbrService = new CBRService(
            $this->cbrClient,
            $this->entityManager,
            $this->cache,
            $this->logger
        );
    }

    public function testUpdateExchangeRatesSuccess(): void
    {
        $currencyCodes = ['USD', 'EUR'];
        $date = new \DateTime();
        $rates = [
            [
                'code' => 'USD',
                'nominal' => '1.0000',
                'value' => '89.5000',
                'date' => $date
            ]
        ];

        $this->cbrClient->expects($this->once())
            ->method('fetchRates')
            ->with($currencyCodes)
            ->willReturn($rates);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ExchangeRate::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->cache->expects($this->once())
            ->method('invalidateTags')
            ->with(['exchange_rates']);

        $this->cbrService->updateExchangeRates($currencyCodes);
    }

    public function testUpdateExchangeRatesWithClientError(): void
    {
        $currencyCodes = ['USD'];
        $exception = new \RuntimeException('API Error');

        $this->cbrClient->expects($this->once())
            ->method('fetchRates')
            ->with($currencyCodes)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to update exchange rates',
                $this->callback(fn($context) =>
                    isset($context['error']) &&
                    isset($context['trace'])
                )
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to update exchange rates: API Error');

        $this->cbrService->updateExchangeRates($currencyCodes);
    }

    public function testSaveRatesWithError(): void
    {
        $currencyCodes = ['USD'];
        $date = new \DateTime();
        $rates = [
            [
                'code' => 'USD',
                'nominal' => '1.0000',
                'value' => '89.5000',
                'date' => $date
            ]
        ];

        $this->cbrClient->expects($this->once())
            ->method('fetchRates')
            ->with($currencyCodes)
            ->willReturn($rates);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willThrowException(new \Exception('Database Error'));

        $this->entityManager->expects($this->once())
            ->method('rollback');

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->willReturnCallback(function($message, $context) {
                static $calls = 0;
                $calls++;

                if ($calls === 1) {
                    $this->assertEquals('Failed to save rates', $message);
                } else {
                    $this->assertEquals('Failed to update exchange rates', $message);
                }

                $this->assertIsArray($context);
                return null;
            });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database Error');

        $this->cbrService->updateExchangeRates($currencyCodes);
    }
}