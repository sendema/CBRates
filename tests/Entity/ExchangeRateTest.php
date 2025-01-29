<?php

namespace App\Tests\Entity;

use App\Entity\ExchangeRate;
use PHPUnit\Framework\TestCase;

class ExchangeRateTest extends TestCase
{
    private ExchangeRate $exchangeRate;

    protected function setUp(): void
    {
        $this->exchangeRate = new ExchangeRate();
    }

    public function testInitialValuesAreNull(): void
    {
        $this->assertNull($this->exchangeRate->getId());
        $this->assertNull($this->exchangeRate->getCurrencyCode());
        $this->assertNull($this->exchangeRate->getValue());
        $this->assertNull($this->exchangeRate->getNominal());
        $this->assertNull($this->exchangeRate->getDate());
        $this->assertNull($this->exchangeRate->getPreviousValue());
        $this->assertNull($this->exchangeRate->getRate());
    }

    public function testExchangeRateSettersAndGetters(): void
    {
        $this->exchangeRate->setCurrencyCode('USD');
        $this->assertEquals('USD', $this->exchangeRate->getCurrencyCode());

        $this->exchangeRate->setValue('75.5000');
        $this->assertEquals('75.5000', $this->exchangeRate->getValue());

        $this->exchangeRate->setNominal('1.0000');
        $this->assertEquals('1.0000', $this->exchangeRate->getNominal());

        $date = new \DateTime('2024-01-29');
        $this->exchangeRate->setDate($date);
        $this->assertEquals($date, $this->exchangeRate->getDate());

        $this->exchangeRate->setPreviousValue('74.8000');
        $this->assertEquals('74.8000', $this->exchangeRate->getPreviousValue());
    }

    public function testGetRate(): void
    {
        $this->exchangeRate->setValue('75.5000');
        $this->exchangeRate->setNominal('1.0000');
        $this->assertEquals(75.50, $this->exchangeRate->getRate());

        $this->exchangeRate->setValue('157.2000');
        $this->exchangeRate->setNominal('10.0000');
        $this->assertEquals(15.72, $this->exchangeRate->getRate());
    }

    public function testGetRateWithNullValues(): void
    {
        $this->exchangeRate->setValue(null);
        $this->exchangeRate->setNominal('1.0000');
        $this->assertNull($this->exchangeRate->getRate());

        $this->exchangeRate->setValue('75.5000');
        $this->exchangeRate->setNominal(null);
        $this->assertNull($this->exchangeRate->getRate());

        $this->exchangeRate->setValue(null);
        $this->exchangeRate->setNominal(null);
        $this->assertNull($this->exchangeRate->getRate());
    }

    /**
     * @dataProvider rateCalculationProvider
     */
    public function testRateCalculationWithDifferentNominals(string $value, string $nominal, float $expected): void
    {
        $this->exchangeRate->setValue($value);
        $this->exchangeRate->setNominal($nominal);

        $this->assertEquals(
            $expected,
            $this->exchangeRate->getRate(),
            "Failed for value {$value} and nominal {$nominal}"
        );
    }

    public function rateCalculationProvider(): array
    {
        return [
            'Standard rate' => ['75.5000', '1.0000', 75.50],
            'Rate with nominal 10' => ['157.2000', '10.0000', 15.72],
            'Rate with nominal 100' => ['3141.6000', '100.0000', 31.42],
            'Rate with comma' => ['75,5000', '1,0000', 75.50],
        ];
    }

    public function testFluentInterface(): void
    {
        $this->assertSame(
            $this->exchangeRate,
            $this->exchangeRate->setCurrencyCode('USD'),
            'setCurrencyCode should return $this'
        );

        $this->assertSame(
            $this->exchangeRate,
            $this->exchangeRate->setValue('75.5000'),
            'setValue should return $this'
        );

        $this->assertSame(
            $this->exchangeRate,
            $this->exchangeRate->setNominal('1.0000'),
            'setNominal should return $this'
        );
    }
}