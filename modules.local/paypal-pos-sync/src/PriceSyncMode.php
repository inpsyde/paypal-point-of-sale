<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync;

interface PriceSyncMode
{
    public const ENABLED = 'gross';

    public const DISABLED = 'zero';
}
