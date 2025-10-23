<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents\Event;

use WC_Product;

/**
 * Represents a change event that is assembled by passing both states via constructor injection
 */
class GenericProductChangeEvent implements ProductChangeEvent
{

    /**
     * @var WC_Product
     */
    private $new;

    /**
     * @var WC_Product
     */
    private $old;

    /**
     * GenericProductChangeEvent constructor.
     *
     * @param WC_Product $new Product in its new state
     * @param WC_Product $old Product in its old state
     */
    public function __construct(WC_Product $new, WC_Product $old)
    {
        $this->new = $new;
        $this->old = $old;
    }

    /**
     * {@inheritDoc}
     */
    public function new(): WC_Product
    {
        return $this->new;
    }

    /**
     * {@inheritDoc}
     */
    public function old(): WC_Product
    {
        return $this->old;
    }
}
