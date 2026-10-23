<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

class ProductBalance
{
    private string $locationUuid;
    private LocationBalanceCollection $variants;
    /**
     * ProductBalance constructor.
     * @param string $locationUuid
     * @param LocationBalanceCollection $variants
     */
    public function __construct(string $locationUuid, LocationBalanceCollection $variants)
    {
        $this->locationUuid = $locationUuid;
        $this->variants = $variants;
    }
    /**
     * @return string
     */
    public function locationUuid(): string
    {
        return $this->locationUuid;
    }
    /**
     * @return LocationBalanceCollection
     */
    public function variants(): LocationBalanceCollection
    {
        return $this->variants;
    }
}
