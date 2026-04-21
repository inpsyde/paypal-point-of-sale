<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

class Balance
{
    private string $productUuid;

    private string $variantUuid;

    private int $balance;

    /**
     * Balance constructor.
     *
     * @param string $productUuid
     * @param string $variantUuid
     * @param int $balance
     */
    public function __construct(
        string $productUuid,
        string $variantUuid,
        int $balance
    ) {

        $this->productUuid = $productUuid;
        $this->variantUuid = $variantUuid;
        $this->balance = $balance;
    }

    /**
     * @return string
     */
    public function productUuid(): string
    {
        return $this->productUuid;
    }

    /**
     * @return string
     */
    public function variantUuid(): string
    {
        return $this->variantUuid;
    }

    /**
     * @return int
     */
    public function balance(): int
    {
        return $this->balance;
    }
}
