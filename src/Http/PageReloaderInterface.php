<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Http;

interface PageReloaderInterface
{
    /**
     * Reload the current URL.
     */
    public function reload(): void;
}
