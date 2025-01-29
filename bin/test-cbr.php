<?php
// bin/test-cbr.php

require dirname(__DIR__).'/vendor/autoload.php';

$date = date('d/m/Y');
$url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date;

echo "Testing CBR API URL: $url\n\n";

try {
    // Используем простой curl запрос
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "HTTP Status Code: $httpCode\n\n";

    if ($response === false) {
        echo "Error: " . curl_error($ch) . "\n";
    } else {
        echo "Response:\n$response\n";

        // Попробуем распарсить XML
        $xml = new SimpleXMLElement($response);
        echo "\nDate from XML: " . $xml['Date'] . "\n";

        foreach ($xml->Valute as $valute) {
            $code = $valute->CharCode;
            $nominal = $valute->Nominal;
            $value = $valute->Value;

            echo "$code: $nominal x $value\n";
        }
    }

    curl_close($ch);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}