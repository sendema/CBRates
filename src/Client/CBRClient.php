<?php

namespace App\Client;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CBRClient
{
    private const CBR_URL = 'http://www.cbr.ru/scripts/XML_daily.asp';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function fetchRates(): string
    {
        $today = new \DateTime();
        $url = self::CBR_URL;

        $this->logger->info('Fetching CBR rates', [
            'url' => $url,
            'date' => $today->format('Y-m-d')
        ]);

        $response = $this->httpClient->request('GET', $url);

        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException(
                sprintf('Failed to fetch rates from CBR. Status code: %d', $response->getStatusCode())
            );
        }

        return $response->getContent();
    }

    public function parseXML(string $xml, array $currencyCodes): array
    {
        $rates = [];
        $doc = new \DOMDocument();

        if (!@$doc->loadXML($xml)) {
            throw new \RuntimeException('Failed to parse XML response from CBR');
        }

        $date = new \DateTime($doc->documentElement->getAttribute('Date'));
        $this->logger->info('Processing rates for date', ['date' => $date->format('Y-m-d')]);

        foreach ($doc->getElementsByTagName('Valute') as $node) {
            $code = $node->getElementsByTagName('CharCode')->item(0)->nodeValue;

            if (!in_array($code, $currencyCodes)) {
                continue;
            }

            $nominal = $node->getElementsByTagName('Nominal')->item(0)->nodeValue;
            $value = str_replace(',', '.', $node->getElementsByTagName('Value')->item(0)->nodeValue);

            $rates[] = [
                'code' => $code,
                'nominal' => $nominal,
                'value' => $value,
                'date' => $date
            ];

            $this->logger->debug('Parsed currency rate', [
                'code' => $code,
                'value' => $value,
                'date' => $date->format('Y-m-d')
            ]);
        }

        return $rates;
    }
}