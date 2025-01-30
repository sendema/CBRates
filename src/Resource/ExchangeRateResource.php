<?php

namespace App\Resource;

use App\Entity\ExchangeRate;

class ExchangeRateResource
{
    public function __construct(private ExchangeRate $rate) {}

    public function toArray(): array
    {
        return [
            'code' => $this->rate->getCurrencyCode(),
            'rate' => $this->rate->getRate(),
            'value' => $this->rate->getValue(),
            'nominal' => $this->rate->getNominal(),
            'previousValue' => $this->rate->getPreviousValue(),
            'change' => $this->calculateChange(),
            'trend' => $this->calculateTrend()
        ];
    }

    private function calculateChange(): ?float
    {
        $current = $this->rate->getValue();
        $previous = $this->rate->getPreviousValue();

        if ($previous === null || $current === null) {
            return 0;
        }
        return round(((float)$current - (float)$previous) / (float)$previous * 100, 2);
    }

    private function calculateTrend(): ?string
    {
        $current = $this->rate->getValue();
        $previous = $this->rate->getPreviousValue();

        if ($previous === null || $current === null) {
            return null;
        }
        return (float)$current > (float)$previous ? 'up' : ((float)$current < (float)$previous ? 'down' : 'same');
    }
}