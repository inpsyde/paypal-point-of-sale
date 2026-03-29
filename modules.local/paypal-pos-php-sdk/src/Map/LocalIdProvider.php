<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;

interface LocalIdProvider
{
    /**
     * @param string $remoteId
     * @throws IdNotFoundException
     * @return int
     */
    public function localId(string $remoteId): int;
}
