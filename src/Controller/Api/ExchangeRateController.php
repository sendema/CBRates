<?php

namespace App\Controller\Api;

use App\Repository\ExchangeRateRepository;
use App\Resource\ExchangeRateResource;
use App\Factory\HistoricalRatesRequestFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private ExchangeRateRepository $rateRepository,
        private ValidatorInterface $validator,
        private HistoricalRatesRequestFactory $requestFactory
    ) {}

    #[Route('/rates/current', name: 'current_rates', methods: ['GET'])]
    public function getCurrentRates(): JsonResponse
    {
        $currencies = ['USD', 'EUR', 'CNY', 'KRW'];
        $rates = $this->rateRepository->findLatestRates($currencies);

        $response = array_map(
            fn($rate) => (new ExchangeRateResource($rate))->toArray(),
            $rates
        );

        return $this->json($response);
    }

    #[Route('/rates/history', name: 'historical_rates', methods: ['GET'])]
    public function getHistoricalRates(Request $request): JsonResponse
    {
        $dto = $this->requestFactory->createFromRequest($request);

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }

            return $this->json([
                'errors' => $errors
            ], 422);
        }
        try {
            $startDateTime = new \DateTime($dto->getStartDate());
            $endDateTime = new \DateTime($dto->getEndDate());

            $historicalRates = $this->rateRepository->findRatesByPeriod(
                $startDateTime,
                $endDateTime,
                $dto->getCurrencies()
            );

            $formattedRates = array_map(
                fn($rate) => (new ExchangeRateResource($rate))->toArray(),
                $historicalRates
            );
            return $this->json($formattedRates);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}