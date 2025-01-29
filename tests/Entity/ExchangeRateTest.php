<?php

namespace App\Tests\Entity;

use App\Entity\ExchangeRate;
use PHPUnit\Framework\TestCase;

class ExchangeRateTest extends TestCase
{
    public function testExchangeRateSettersAndGetters()
    {
        $exchangeRate = new ExchangeRate();

        $exchangeRate->setCurrencyCode('USD');
        $this->assertEquals('USD', $exchangeRate->getCurrencyCode());

        $exchangeRate->setValue('75.5');
        $this->assertEquals('75.5', $exchangeRate->getValue());

        $exchangeRate->setNominal('1');
        $this->assertEquals('1', $exchangeRate->getNominal());

        $date = new \DateTime();
        $exchangeRate->setDate($date);
        $this->assertEquals($date, $exchangeRate->getDate());

        $exchangeRate->setPreviousValue('74.8');
        $this->assertEquals('74.8', $exchangeRate->getPreviousValue());
    }

    public function testGetRate()
    {
        $exchangeRate = new ExchangeRate();
        $exchangeRate->setValue('75.5');
        $exchangeRate->setNominal('1');

        $this->assertEquals(75.5, $exchangeRate->getRate());

        $exchangeRate->setValue('157.2');
        $exchangeRate->setNominal('10');
        $this->assertEquals(15.72, round($exchangeRate->getRate(), 2));

        $nullExchangeRate = new ExchangeRate();
        $this->assertNull($nullExchangeRate->getRate());
    }

    public function testRateCalculationWithDifferentNominals()
    {
        $testCases = [
            ['value' => '75.5', 'nominal' => '1', 'expected' => 75.5],
            ['value' => '157.2', 'nominal' => '10', 'expected' => 15.72],
            ['value' => '3141.6', 'nominal' => '100', 'expected' => 31.416],
        ];

        foreach ($testCases as $case) {
            $exchangeRate = new ExchangeRate();
            $exchangeRate->setValue($case['value']);
            $exchangeRate->setNominal($case['nominal']);

            $this->assertEquals(
                round($case['expected'], 2),
                round($exchangeRate->getRate(), 2),
                "Failed for value {$case['value']} and nominal {$case['nominal']}"
            );
        }
    }
}