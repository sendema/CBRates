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

    public function fetchRates(array $currencyCodes): array
    {
        try {
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

            $xml = $response->getContent();
            return $this->parseXML($xml, $currencyCodes);

        } catch (\Exception $e) {
            $this->logger->error('Error fetching rates from CBR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function parseXML(string $xml, array $currencyCodes): array
    {
        try {
            $doc = new \DOMDocument();

            if (!@$doc->loadXML($xml)) {
                throw new \RuntimeException('Failed to parse XML response from CBR');
            }

            $date = new \DateTime($doc->documentElement->getAttribute('Date'));
            $this->logger->info('Processing rates for date', [
                'date' => $date->format('Y-m-d')
            ]);

            $rates = [];
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

        } catch (\Exception $e) {
            $this->logger->error('Error parsing XML from CBR', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}