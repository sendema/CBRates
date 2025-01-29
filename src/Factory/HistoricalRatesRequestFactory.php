<?php

namespace App\Factory;

use App\DTO\HistoricalRatesRequest;
use Symfony\Component\HttpFoundation\Request;

class HistoricalRatesRequestFactory
{
    public function createFromRequest(Request $request): HistoricalRatesRequest
    {
        $currencies = $request->query->get('currencies');
        $currencyArray = $currencies ? explode(',', $currencies) : [];

        return new HistoricalRatesRequest(
            $request->query->get('start_date'),
            $request->query->get('end_date'),
            $currencyArray
        );
    }
}