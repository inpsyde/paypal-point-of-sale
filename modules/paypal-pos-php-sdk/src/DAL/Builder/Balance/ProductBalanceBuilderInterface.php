<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Balance;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\ProductBalance;
interface ProductBalanceBuilderInterface
{
    /**
     * @param array $data
     *
     * @return ProductBalance
     */
    public function buildFromArray(array $data): ProductBalance;
    /**
     * @param ProductBalance $productBalance
     *
     * @return array
     */
    public function createDataArray(ProductBalance $productBalance): array;
}
