<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

interface EventName
{
    public const INVENTORY_BALANCE_CHANGED = 'InventoryBalanceChanged';
}
