<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Type\LocationType;

class LocationBalanceFactory
{
    /**
     * @param string $locationUuid
     * @param string $locationType
     * @param string $productUuid
     * @param string $variantUuid
     * @param string $balance
     *
     * @return LocationBalance
     */
    public function create(
        string $locationUuid,
        string $locationType,
        string $productUuid,
        string $variantUuid,
        string $balance
    ): LocationBalance {

        return new LocationBalance(
            $locationUuid,
            LocationType::get($locationType),
            $productUuid,
            $variantUuid,
            (int) $balance
        );
    }
}
