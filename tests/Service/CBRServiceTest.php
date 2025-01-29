<?php
namespace App\Tests\Service;

use App\Entity\ExchangeRate;
use App\Service\CBRService;
use App\Service\RedisCacheService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CBRServiceTest extends TestCase
{
    /** @var HttpClientInterface|MockObject */
    private $httpClient;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var RedisCacheService|MockObject */
    private $cacheService;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CBRService */
    private $cbrService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cacheService = $this->createMock(RedisCacheService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cbrService = new CBRService(
            $this->httpClient,
            $this->entityManager,
            $this->cacheService,
            $this->logger
        );
    }

    public function testUpdateExchangeRatesWithHttpError()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')
            ->willReturn(500);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to update exchange rates'),
                $this->isType('array')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to fetch rates/');

        $this->cbrService->updateExchangeRates(['USD']);
    }
}