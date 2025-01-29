<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class HistoricalRatesRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Start date is required')]
        #[Assert\Date(message: 'Invalid start date format')]
        private ?string $startDate,

        #[Assert\NotBlank(message: 'End date is required')]
        #[Assert\Date(message: 'Invalid end date format')]
        private ?string $endDate,

        #[Assert\All([
            new Assert\Currency(message: 'Invalid currency code: {{ value }}')
        ])]
        private ?array $currencies = []
    ) {}

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getCurrencies(): array
    {
        return $this->currencies ?? [];
    }

    #[Assert\IsTrue(message: 'Start date must be before or equal to end date')]
    public function isValidDateRange(): bool
    {
        if (!$this->startDate || !$this->endDate) {
            return true;
        }

        $start = new \DateTime($this->startDate);
        $end = new \DateTime($this->endDate);

        return $start <= $end;
    }

    #[Assert\IsTrue(message: 'Date range too large. Maximum 3 months allowed')]
    public function isValidDatePeriod(): bool
    {
        if (!$this->startDate || !$this->endDate) {
            return true;
        }

        $start = new \DateTime($this->startDate);
        $end = new \DateTime($this->endDate);

        $maxEnd = (clone $start)->modify('+3 months');

        return $end <= $maxEnd;
    }
}