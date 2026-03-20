<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
interface LocalIdProvider
{
    /**
     * @param string $remoteId
     * @throws IdNotFoundException
     * @return int
     */
    public function localId(string $remoteId): int;
}
