<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;

interface RemoteIdProvider
{
    /**
     * @param int $localId
     *
     * @return string
     * @throws IdNotFoundException
     */
    public function remoteId(int $localId): string;
}
