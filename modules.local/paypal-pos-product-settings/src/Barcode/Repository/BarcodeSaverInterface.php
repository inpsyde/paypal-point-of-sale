<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository;

use WC_Product;

/**
 * The interface for saving barcodes.
 */
interface BarcodeSaverInterface
{
    /**
     * Saves barcode.
     * @param WC_Product $owner The product (or variation) to which this barcode belongs to.
     * @param string $barcode
     * @return void
     */
    public function save(WC_Product $owner, string $barcode): void;
}
