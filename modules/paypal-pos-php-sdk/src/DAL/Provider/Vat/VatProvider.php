<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Vat\VatNotFound;
use WC_Product;
interface VatProvider
{
    /**
     * @param WC_Product $wcProduct
     * @return Vat
     * @throws VatNotFound
     * @throws Exception
     */
    public function provide(WC_Product $wcProduct): Vat;
}
