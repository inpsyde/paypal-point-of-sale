<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository;

use WC_Product;
/**
 * The interface for retrieving barcodes.
 */
interface BarcodeRetrieverInterface
{
    /**
     * Returns the barcode belonging to the product/variation or null.
     * @param WC_Product $owner
     * @return string|null
     */
    public function get(WC_Product $owner): ?string;
}
