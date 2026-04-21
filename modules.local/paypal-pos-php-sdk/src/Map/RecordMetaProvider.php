<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map;

interface RecordMetaProvider
{
    public function metaData(int $localId, string $remoteId): array;
}
