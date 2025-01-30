<?php

namespace App\Service\CBR;

use App\Client\CBRClient;
use App\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CBRService
{
    private const CACHE_TTL = 3600;
    private const CACHE_PREFIX = 'cbr_rates_';

    public function __construct(
        private CBRClient $cbrClient,
        private EntityManagerInterface $entityManager,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function updateExchangeRates(array $currencyCodes): void
    {
        try {
            $rates = $this->cbrClient->fetchRates($currencyCodes);
            $this->saveRates($rates);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update exchange rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to update exchange rates: ' . $e->getMessage(), 0, $e);
        }
    }

    private function saveRates(array $rates): void
    {
        $this->entityManager->beginTransaction();

        try {
            foreach ($rates as $rateData) {
                $existingRate = $this->entityManager->getRepository(ExchangeRate::class)
                    ->findOneBy([
                        'currencyCode' => $rateData['code'],
                        'date' => $rateData['date']
                    ]);

                if ($existingRate) {
                    $existingRate->setValue($rateData['value'])
                        ->setNominal($rateData['nominal']);
                } else {
                    $rate = new ExchangeRate();
                    $rate->setCurrencyCode($rateData['code'])
                        ->setValue($rateData['value'])
                        ->setNominal($rateData['nominal'])
                        ->setDate($rateData['date'])
                        ->setPreviousValue($this->findPreviousValue($rateData['code'], $rateData['date']));

                    $this->entityManager->persist($rate);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
            $this->cache->invalidateTags(['exchange_rates']);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to save rates', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function findPreviousValue(string $currencyCode, \DateTime $currentDate): ?string
    {
        $previousRate = $this->entityManager->getRepository(ExchangeRate::class)
            ->createQueryBuilder('er')
            ->where('er.currencyCode = :code')
            ->andWhere('er.date < :currentDate')
            ->setParameter('code', $currencyCode)
            ->setParameter('currentDate', $currentDate)
            ->orderBy('er.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $previousRate ? $previousRate->getValue() : null;
    }
}