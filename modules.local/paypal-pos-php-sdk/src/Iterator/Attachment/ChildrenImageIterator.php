<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment;

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\WcProductIterator;
use WC_Product;
use WC_Product_Variable;

/**
 * Returns the featured image attachment ID ($product->get_image_id())
 * of all the product's children.
 * You can use this to fetch all product images of variations of a variable product
 */
class ChildrenImageIterator implements WcProductIterator
{
    /**
     * @var int[]
     */
    private array $children = [];

    private int $key = 0;

    private WC_Product $product;

    public function __construct(WC_Product $product)
    {
        $this->product = $product;
        $this->rewind();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $product = wc_get_product(current($this->children));

        return $product ? $product->get_image_id() : 0;
    }

    public function next(): void
    {
        next($this->children);
        $this->key++;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return current($this->children) !== false;
    }

    public function rewind(): void
    {
        $this->key = 0;
        /**
         * If we know we're dealing with a variable product,
         * use its more specific method that gathers only children that are
         * visible. Makes more sense to use that then.
         */
        if ($this->product instanceof WC_Product_Variable) {
            $this->children = $this->product->get_visible_children();
            return;
        }
        $this->children = $this->product->get_children();
    }

    /**
     * {@inheritDoc}
     */
    public function switchProduct(WC_Product $product): void
    {
        $this->product = $product;
        $this->rewind();
    }
}
