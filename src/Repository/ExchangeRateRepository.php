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

    /**
     * Находит последние курсы для указанных валют
     */
    public function findLatestRates(array $currencyCodes): array
    {
        return $this->createQueryBuilder('er')
            ->where('er.currencyCode IN (:codes)')
            ->andWhere('er.date = (
                SELECT MAX(er2.date) 
                FROM App\Entity\ExchangeRate er2 
                WHERE er2.currencyCode = er.currencyCode
            )')
            ->setParameter('codes', $currencyCodes)
            ->getQuery()
            ->getResult();
    }

    public function findOneByDateAndCode(string $code, \DateTime $date): ?ExchangeRate
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.currencyCode = :code')
            ->andWhere('e.date = :date')
            ->setParameter('code', $code)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }
}