<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\OrderSync\OrderSyncModule;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class OrderSyncModuleTest extends BrainMonkeyWpTestCase
{
    /**
     * @dataProvider originLabels
     */
    public function testFilterOriginLabel(string $sourceType, string $source, string $expected): void
    {
        self::assertSame($expected, OrderSyncModule::filterOriginLabel('Source: %s', $sourceType, $source));
    }

    public function originLabels(): array
    {
        $posType = PurchaseOrderFactory::ORIGIN_SOURCE_TYPE;
        $posSource = PurchaseOrderFactory::ORIGIN_SOURCE;

        return [
            'POS order' => [$posType, $posSource, ''],
            'other source' => [$posType, 'newsletter', 'Source: %s'],
            'other source type' => ['referral', $posSource, 'Source: %s'],
        ];
    }
}
