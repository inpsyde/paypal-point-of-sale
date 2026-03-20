<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Status;

interface SyncStatusCodes
{
    public const NO_VALID_PRODUCT_ID = 'no-valid-product-id';
    public const SYNCED = 'synced';
    public const NOT_SYNCED = 'not-synced';
    public const SYNCABLE = 'syncable';
    public const NOT_SYNCABLE = 'not-syncable';
    public const PRODUCT_NOT_FOUND = 'product-not-found';
    public const UNSUPPORTED_PRODUCT_TYPE = 'unsupported-product-type';
    public const EXCLUDED = 'excluded';
    public const UNPUBLISHED = 'unpublished';
    public const UNPURCHASABLE = 'unpurchasable';
    public const INVISIBLE = 'invisible';
    public const UNDEFINED = 'undefined';
}
