<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment;

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\WcProductIterator;
use WC_Product;

/**
 * This class is admittedly a bit weird because it "iterates" over a single value.
 * But this construct allows us to combine it with other Iterators in a general-purpose
 * WcProductIteratorAggregate.
 */
class FeaturedImageIterator implements WcProductIterator
{

    /**
     * @var int The featured image attachment ID
     */
    private $attachmentId;

    /**
     * @var bool Used to check if the attachment ID has already been returned
     */
    private $called = false;

    /**
     * @var WC_Product
     */
    private $product;

    public function __construct(WC_Product $product)
    {
        $this->product = $product;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $this->called = true;

        return $this->attachmentId;
    }

    public function next(): void
    {
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return 0;
    }

    public function valid(): bool
    {
        return !$this->called and $this->attachmentId;
    }

    public function rewind(): void
    {
        $this->called = false;
        $this->attachmentId = $this->product->get_image_id();
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
