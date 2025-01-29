<?php

namespace App\Controller\Api;

use App\Repository\ExchangeRateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private ExchangeRateRepository $repository
    ) {}

    #[Route('/rates/current', name: 'current_rates', methods: ['GET'])]
    public function getCurrentRates(): JsonResponse
    {
        $currencies = ['USD', 'EUR', 'GBP', 'CNY'];
        $rates = $this->repository->findLatestRates($currencies);

        $response = array_map(function($rate) {
            return [
                'code' => $rate->getCurrencyCode(),
                'rate' => $rate->getRate(),
                'value' => $rate->getValue(),
                'nominal' => $rate->getNominal(),
                'previousValue' => $rate->getPreviousValue(),
                'change' => $this->calculateChange($rate->getValue(), $rate->getPreviousValue()),
                'trend' => $this->calculateTrend($rate->getValue(), $rate->getPreviousValue())
            ];
        }, $rates);

        return $this->json($response);
    }

    private function calculateChange(?string $current, ?string $previous): ?float
    {
        if ($previous === null || $current === null) {
            return null;
        }
        return round(((float)$current - (float)$previous) / (float)$previous * 100, 2);
    }

    private function calculateTrend(?string $current, ?string $previous): ?string
    {
        if ($previous === null || $current === null) {
            return null;
        }
        return (float)$current > (float)$previous ? 'up' : ((float)$current < (float)$previous ? 'down' : 'same');
    }
}