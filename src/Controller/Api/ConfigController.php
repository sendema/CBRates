<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    public function __construct(
        private array $exchangeRatesConfig
    ) {}

    #[Route('/api/config/exchange-rates', name: 'api_config_exchange_rates', methods: ['GET'])]
    public function getExchangeRatesConfig(): JsonResponse
    {
        return $this->json([
            'widgetUpdateInterval' => $this->exchangeRatesConfig['widget_update_interval'],
            'displayCurrencies' => $this->exchangeRatesConfig['display_currencies']
        ]);
    }
}