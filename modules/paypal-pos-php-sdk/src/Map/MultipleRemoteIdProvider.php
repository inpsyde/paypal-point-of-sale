<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
interface MultipleRemoteIdProvider
{
    /**
     * @param int $localId
     *
     * @return string[]
     *
     * @throws IdNotFoundException
     */
    public function remoteIds(int $localId): array;
}
