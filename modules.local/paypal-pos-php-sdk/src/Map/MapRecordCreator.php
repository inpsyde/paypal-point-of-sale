<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;

interface MapRecordCreator
{

    /**
     * @param int $localId
     * @param string $remoteId
     * @param array $arguments
     *
     * @return bool
     */
    public function createRecord(int $localId, string $remoteId, array $arguments = []): bool;

    /**
     * @param int $localId
     * @param string $remoteId
     *
     * @return bool
     *
     * @throws IdNotFoundException
     */
    public function deleteRecord(int $localId, string $remoteId): bool;
}
