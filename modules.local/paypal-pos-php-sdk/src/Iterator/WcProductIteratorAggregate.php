<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Iterator;

use WC_Product;

/**
 * Iterates over a collection of WcProductIterator instances as if they were
 * one large Iterator
 */
class WcProductIteratorAggregate implements WcProductIterator
{
    /**
     * @var WcProductIterator[]
     */
    private array $iterators;

    private ?WC_Product $currentProduct = null;

    /**
     * @var WcProductIterator|null|false
     */
    private $currentIterator = null;

    /**
     * @var int The key of the current value
     */
    protected int $key = 0;

    public function __construct(WcProductIterator ...$iterators)
    {
        $this->iterators = $iterators;
        $this->rewind();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        assert($this->currentIterator instanceof WcProductIterator);
        
        return $this->currentIterator->current();
    }

    public function next(): void
    {
        assert($this->currentIterator instanceof WcProductIterator);

        $this->key++;
        $this->currentIterator->next();
        if (!$this->valid()) {
            $this->advanceIterator();
        }
    }

    /**
     * Move on to the next iterator and check if it is has data.
     * If not, recurse until an iterator with data is found.
     */
    private function advanceIterator()
    {
        $this->currentIterator = next($this->iterators);
        if (!$this->valid() && $this->currentIterator instanceof WcProductIterator) {
            $this->advanceIterator();
        }
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return $this->currentProduct and $this->currentIterator and $this->currentIterator->valid();
    }

    public function rewind(): void
    {
        foreach ($this->iterators as $iterator) {
            $iterator->rewind();
        }
        $this->currentIterator = reset($this->iterators);
        $this->key = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function switchProduct(WC_Product $product): void
    {
        $this->currentProduct = $product;
        foreach ($this->iterators as $iterator) {
            $iterator->switchProduct($product);
        }
        $this->rewind();
    }
}
