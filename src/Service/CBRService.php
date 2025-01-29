<?php

namespace App\Service;

use App\Entity\ExchangeRate;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CBRService
{
    private const CBR_URL = 'http://www.cbr.ru/scripts/XML_daily.asp';
    private const CACHE_TTL = 3600;
    private const CACHE_PREFIX = 'cbr_rates_';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private RedisCacheService $cache,
        private LoggerInterface $logger
    ) {}

    public function updateExchangeRates(array $currencyCodes): void
    {
        try {
            $xml = $this->fetchRatesWithCache();
            $rates = $this->parseXML($xml, $currencyCodes);
            $this->saveRatesWithCache($rates);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update exchange rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to update exchange rates: ' . $e->getMessage(), 0, $e);
        }
    }

    private function fetchRatesWithCache(): string
    {
        $today = new \DateTime();
        $url = self::CBR_URL;

        $this->logger->info('Fetching CBR rates', [
            'url' => $url,
            'date' => $today->format('Y-m-d')
        ]);

        $response = $this->httpClient->request('GET', $url);

        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException(
                sprintf('Failed to fetch rates from CBR. Status code: %d', $response->getStatusCode())
            );
        }

        return $response->getContent();
    }

    private function parseXML(string $xml, array $currencyCodes): array
    {
        $rates = [];
        $doc = new \DOMDocument();

        if (!@$doc->loadXML($xml)) {
            throw new \RuntimeException('Failed to parse XML response from CBR');
        }

        $date = new \DateTime($doc->documentElement->getAttribute('Date'));
        $this->logger->info('Processing rates for date', ['date' => $date->format('Y-m-d')]);

        foreach ($doc->getElementsByTagName('Valute') as $node) {
            $code = $node->getElementsByTagName('CharCode')->item(0)->nodeValue;

            if (!in_array($code, $currencyCodes)) {
                continue;
            }

            $nominal = $node->getElementsByTagName('Nominal')->item(0)->nodeValue;
            $value = str_replace(',', '.', $node->getElementsByTagName('Value')->item(0)->nodeValue);

            $rates[] = [
                'code' => $code,
                'nominal' => $nominal,
                'value' => $value,
                'date' => $date
            ];

            $this->logger->debug('Parsed currency rate', [
                'code' => $code,
                'value' => $value,
                'date' => $date->format('Y-m-d')
            ]);
        }

        return $rates;
    }

    private function saveRatesWithCache(array $rates): void
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