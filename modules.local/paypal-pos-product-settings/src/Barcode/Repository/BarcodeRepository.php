<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository;

use WC_Product;

/**
 * Saves/retrieves barcode via WC meta data.
 */
class BarcodeRepository implements BarcodeSaverInterface, BarcodeRetrieverInterface
{
    private string $key;

    private string $filterName;

    /**
     * @param string $key Meta data key where barcode is stored.
     * @param string $filterName The name of WP filter that can be used
     * to get the barcode value instead of meta.
     * Accepts WC_Product, returns string.
     */
    public function __construct(string $key, string $filterName)
    {
        $this->key = $key;
        $this->filterName = $filterName;
    }

    public function save(WC_Product $owner, string $barcode): void
    {
        $owner->add_meta_data($this->key, $barcode, true);
        $owner->save_meta_data();
    }

    public function get(WC_Product $owner): ?string
    {
        $barcode = $owner->get_meta($this->key);

        return apply_filters($this->filterName, $barcode, $owner);
    }
}
