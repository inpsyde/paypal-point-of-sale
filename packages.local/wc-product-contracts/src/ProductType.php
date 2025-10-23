<?php

namespace Inpsyde\WcProductContracts;

interface ProductType
{
    /**
     * Type string for Simple Product
     */
    public const SIMPLE = 'simple';

    /**
     * Type string for Variable Product
     */
    public const VARIABLE = 'variable';

    /**
     * Type string for Product Variation
     */
    public const VARIATION = 'variation';
}
