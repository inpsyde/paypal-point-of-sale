<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\OrderSync\PosOrdersModule;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class PosOrdersModuleTest extends BrainMonkeyWpTestCase
{
    /**
     * @dataProvider originLabels
     */
    public function testFilterOriginLabel(string $sourceType, string $source, string $expected): void
    {
        self::assertSame($expected, PosOrdersModule::filterOriginLabel('Source: %s', $sourceType, $source));
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

    public function testResetAndUninstallDeleteTheImportOptions(): void
    {
        $options = (new PosOrdersModule())->extensions()['paypal-pos.onboarding.resettable.options'](['other']);

        self::assertSame(['other', PurchaseBackfill::STATUS_OPTION, PurchaseBackfill::WEBHOOK_GAP_OPTION], $options);
    }
}
