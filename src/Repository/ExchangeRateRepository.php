<?php

namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ExchangeRateRepository extends ServiceEntityRepository
{
    private const CACHE_TTL = 3600;

    public function __construct(
        ManagerRegistry $registry,
        private TagAwareCacheInterface $cache
    ) {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function findLatestRates(array $currencyCodes): array
    {
        $cacheKey = 'latest_rates_' . md5(implode('_', $currencyCodes));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($currencyCodes) {
            $item->expiresAfter(self::CACHE_TTL);
            $item->tag(['exchange_rates']);

            $qb = $this->createQueryBuilder('er')
                ->where('er.currencyCode IN (:codes)')
                ->andWhere('er.date = (
                    SELECT MAX(er2.date) 
                    FROM App\Entity\ExchangeRate er2 
                    WHERE er2.currencyCode = er.currencyCode
                )')
                ->setParameter('codes', $currencyCodes);

            $rates = $qb->getQuery()->getResult();

            if (empty($rates)) {
                \dump($qb->getQuery()->getSQL());
                \dump($currencyCodes);
            }

            return $rates;
        });
    }

    public function findRatesByPeriod(\DateTime $startDate, \DateTime $endDate, array $currencies = []): array
    {
        $cacheKey = sprintf(
            'historical_rates_%s_%s_%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            md5(implode('_', $currencies))
        );

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($startDate, $endDate, $currencies) {
            $item->expiresAfter(self::CACHE_TTL);
            $item->tag(['exchange_rates']);

            $qb = $this->createQueryBuilder('er')
                ->where('er.date BETWEEN :start AND :end');

            if (!empty($currencies)) {
                $qb->andWhere('er.currencyCode IN (:currencies)')
                    ->setParameter('currencies', $currencies);
            }

            $qb->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->orderBy('er.date', 'ASC')
                ->addOrderBy('er.currencyCode', 'ASC');

            return $qb->getQuery()->getResult();
        });
    }
}