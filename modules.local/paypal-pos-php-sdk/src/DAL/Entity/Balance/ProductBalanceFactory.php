<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

class ProductBalanceFactory
{
    /**
     * @param string $locationUuid
     * @param LocationBalanceCollection $variants
     *
     * @return ProductBalance
     */
    public function create(
        string $locationUuid,
        LocationBalanceCollection $variants
    ): ProductBalance {

        return new ProductBalance(
            $locationUuid,
            $variants
        );
    }
}
