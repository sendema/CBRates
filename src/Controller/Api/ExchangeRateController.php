<?php

namespace App\Controller\Api;

use App\Repository\ExchangeRateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private ExchangeRateRepository $rateRepository
    ) {}

    #[Route('/rates/current', name: 'current_rates', methods: ['GET'])]
    public function getCurrentRates(): JsonResponse
    {
        $currencies = ['USD', 'EUR', 'CNY', 'KRW'];
        $rates = $this->rateRepository->findLatestRates($currencies);

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

    #[Route('/rates/history', name: 'historical_rates', methods: ['GET'])]
    public function getHistoricalRates(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $currencies = $request->query->get('currencies', '');

        if (!$startDate || !$endDate) {
            return $this->json([
                'error' => 'Start date and end date are required'
            ], 400);
        }

        try {
            $startDateTime = new \DateTime($startDate);
            $endDateTime = new \DateTime($endDate);

            if ($startDateTime > $endDateTime) {
                return $this->json([
                    'error' => 'Start date must be before or equal to end date'
                ], 400);
            }

            $maxDateRange = clone $startDateTime;
            $maxDateRange->modify('+3 months');
            if ($endDateTime > $maxDateRange) {
                return $this->json([
                    'error' => 'Date range too large. Maximum 3 months allowed'
                ], 400);
            }

            $currencyList = $currencies ? explode(',', $currencies) : [];
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid date or currency format',
                'message' => $e->getMessage()
            ], 400);
        }

        $historicalRates = $this->rateRepository->findRatesByPeriod(
            $startDateTime,
            $endDateTime,
            $currencyList
        );

        $formattedRates = array_map(function($rate) {
            return [
                'date' => $rate->getDate()->format('Y-m-d'),
                'currency' => $rate->getCurrencyCode(),
                'rate' => $rate->getRate(),
                'value' => $rate->getValue(),
                'nominal' => $rate->getNominal(),
                'previousValue' => $rate->getPreviousValue(),
                'change' => $this->calculateChange($rate->getValue(), $rate->getPreviousValue()),
                'trend' => $this->calculateTrend($rate->getValue(), $rate->getPreviousValue())
            ];
        }, $historicalRates);

        return $this->json($formattedRates);
    }
}