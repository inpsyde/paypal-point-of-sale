<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Iterator;

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment\ChildrenImageIterator;
use Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment\FeaturedImageIterator;
use Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment\GalleryImageIterator;
use WC_Product;

/**
 * Iterates over all image IDs of a WC_Product
 * First, it returns the featured image
 * Then it returns the featured image of  all children (->variations)
 * Then it returns all gallery image IDs.
 * You can limit the number of IDs with the $limit parameter.
 *
 * This class is basically just a sugar on top of WcProductIteratorAggregate
 */
class WcProductAttachmentIterator extends WcProductIteratorAggregate
{
    private int $limit;

    public function __construct(WC_Product $product, int $limit = 0, WcProductIterator ...$iterators)
    {
        $this->limit = $limit;
        /**
         * Use default iterators if none are passed.
         * Passing iterators is probably only useful for testing right now
         */
        if (empty($iterators)) {
            $iterators = [
                new FeaturedImageIterator($product),
                new ChildrenImageIterator($product),
                new GalleryImageIterator($product),
            ];
        }
        parent::__construct(...$iterators);
        $this->switchProduct($product);
    }

    public function valid(): bool
    {
        if (!parent::valid()) {
            return false;
        }
        if ($this->limit !== 0 && $this->key > $this->limit) {
            return false;
        }

        return true;
    }

    public function current(): int
    {
        return (int) parent::current();
    }
}
