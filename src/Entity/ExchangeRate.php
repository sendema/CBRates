<?php
// src/Entity/ExchangeRate.php
namespace App\Entity;

use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
#[ORM\Table(name: 'exchange_rates')]
class ExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 3)]
    private ?string $currencyCode = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private ?string $value = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private ?string $nominal = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, nullable: true)]
    private ?string $previousValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getNominal(): ?string
    {
        return $this->nominal;
    }

    public function setNominal(string $nominal): self
    {
        $this->nominal = $nominal;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPreviousValue(): ?string
    {
        return $this->previousValue;
    }

    public function setPreviousValue(?string $previousValue): self
    {
        $this->previousValue = $previousValue;
        return $this;
    }

    public function getRate(): ?float
    {
        if ($this->value === null || $this->nominal === null) {
            return null;
        }

        // Заменить запятую на точку для корректного парсинга
        $value = str_replace(',', '.', $this->value);
        $nominal = str_replace(',', '.', $this->nominal);

        // Округление до 2 знаков после запятой
        return round((float) $value / (float) $nominal, 2);
    }
}