<?php

namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function findLatestRates(array $currencyCodes): array
    {
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
    }
    public function findRatesByPeriod(\DateTime $startDate, \DateTime $endDate, array $currencies = []): array
    {
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
    }
}