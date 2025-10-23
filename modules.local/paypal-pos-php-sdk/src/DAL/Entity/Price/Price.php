<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price;

class Price
{

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $currencyId;

    /**
     * Price constructor.
     *
     * @param int $amount
     * @param string $currencyId
     */
    public function __construct(
        int $amount,
        string $currencyId
    ) {
        $this->amount = $amount;
        $this->currencyId = $currencyId;
    }

    /**
     * @return int
     */
    public function amount(): int
    {
        return $this->amount;
    }

    /**
     * Convert Integer Price to Float amount
     * Note: Converting from hundreth-based integer to float
     *
     * @return float
     */
    public function amountToFloat(): float
    {
        return ((float) $this->amount) / 100;
    }

    /**
     * @param int $amount
     *
     * @return Price
     */
    public function setAmount(int $amount): Price
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function currencyId(): string
    {
        return $this->currencyId;
    }

    /**
     * @param string $currencyId
     *
     * @return Price
     */
    public function setCurrencyId(string $currencyId): Price
    {
        $this->currencyId = $currencyId;

        return $this;
    }
}
