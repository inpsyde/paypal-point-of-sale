<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\OrderSyncSettingsFilter;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class OrderSyncSettingsFilterTest extends BrainMonkeyWpTestCase
{
    public function testAClearedPaymentTypeSelectionIsSaved(): void
    {
        $filter = new OrderSyncSettingsFilter();

        self::assertSame(
            ['order_sync_enabled' => 'enabled', 'order_sync_payment_types' => []],
            $filter->filterSettingsValues(['order_sync_enabled' => 'enabled'])
        );
        // During onboarding the order sync section isn't posted, so nothing is cleared.
        self::assertSame(['sync_collision_strategy' => 'merge'], $filter->filterSettingsValues(['sync_collision_strategy' => 'merge']));
    }
}
