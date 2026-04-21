<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat;

final class Vat
{
    private float $percentage;
    /**
     * Vat constructor.
     * @param float $percentage
     */
    public function __construct(float $percentage)
    {
        $this->percentage = $percentage;
    }
    /**
     * @return float
     */
    public function percentage(): float
    {
        return $this->percentage;
    }
}
