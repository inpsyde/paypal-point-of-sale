<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Discount;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount\DiscountCollection;
interface DiscountCollectionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return DiscountCollection
     */
    public function buildFromArray(array $data): DiscountCollection;
    /**
     * @param DiscountCollection $discountCollection
     *
     * @return array
     */
    public function createDataArray(DiscountCollection $discountCollection): array;
}
