<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Type\LocationType;

class LocationBalance
{
    /**
     * @var string
     */
    private $locationUuid;

    /**
     * @var LocationType
     */
    private $locationType;

    /**
     * @var string
     */
    private $productUuid;

    /**
     * @var string
     */
    private $variantUuid;

    /**
     * @var int
     */
    private $balance;

    /**
     * LocationBalance constructor.
     * @param string $locationUuid
     * @param LocationType $locationType
     * @param string $productUuid
     * @param string $variantUuid
     * @param int $balance
     */
    public function __construct(
        string $locationUuid,
        LocationType $locationType,
        string $productUuid,
        string $variantUuid,
        int $balance
    ) {

        $this->locationUuid = $locationUuid;
        $this->locationType = $locationType;
        $this->productUuid = $productUuid;
        $this->variantUuid = $variantUuid;
        $this->balance = $balance;
    }

    /**
     * @return string
     */
    public function locationUuid(): string
    {
        return $this->locationUuid;
    }

    /**
     * @return LocationType
     */
    public function locationType(): LocationType
    {
        return $this->locationType;
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
