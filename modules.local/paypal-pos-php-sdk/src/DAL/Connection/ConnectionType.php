<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection;

interface ConnectionType
{
    const PRODUCT = 'product';
    const VARIANT = 'variant';
    const IMAGE = 'image';
}
