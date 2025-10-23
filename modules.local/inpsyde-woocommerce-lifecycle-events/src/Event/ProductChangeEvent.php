<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents\Event;

use WC_Product;

interface ProductChangeEvent
{

    /**
     * An instance of the WC_Product before the change was applied
     *
     * @return WC_Product
     */
    public function new(): WC_Product;

    /**
     * An instance of the WC_Product after the change was applied
     *
     * @return WC_Product
     */
    public function old(): WC_Product;
}
