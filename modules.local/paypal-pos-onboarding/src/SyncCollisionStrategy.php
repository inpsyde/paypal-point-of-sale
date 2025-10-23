<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding;

interface SyncCollisionStrategy
{
    public const WIPE = 'wipe';

    public const MERGE = 'merge';
}
