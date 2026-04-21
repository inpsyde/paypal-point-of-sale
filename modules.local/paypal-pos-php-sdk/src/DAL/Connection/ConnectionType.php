<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection;

interface ConnectionType
{
    public const PRODUCT = 'product';
    public const VARIANT = 'variant';
    public const IMAGE = 'image';
}
