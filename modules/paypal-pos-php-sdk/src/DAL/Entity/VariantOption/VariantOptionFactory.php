<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption;

class VariantOptionFactory
{
    /**
     * @param string $name
     * @param string $value
     *
     * @return VariantOption
     */
    public function create(string $name, string $value): VariantOption
    {
        return new VariantOption($name, $value);
    }
}
