<?php
declare(strict_types=1);

class WC_Product_Simple extends WC_Product
{
    /**
     * Get internal type.
     *
     * @return string
     */
    public function get_type() {
        return 'simple';
    }
}