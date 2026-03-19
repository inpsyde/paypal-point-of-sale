<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Iterator;

use Iterator;
use WC_Product;
interface WcProductIterator extends Iterator
{
    /**
     * Makes the Iterator traverse a different WC_Product instance.
     * the Iterator MUST rewind when receiving a new product
     *
     * @param WC_Product $product
     */
    public function switchProduct(WC_Product $product): void;
}
