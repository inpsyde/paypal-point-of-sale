<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Discount;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount\Discount;
interface DiscountBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Discount
     */
    public function buildFromArray(array $data): Discount;
    /**
     * @param Discount $discount
     *
     * @return array
     */
    public function createDataArray(Discount $discount): array;
}
