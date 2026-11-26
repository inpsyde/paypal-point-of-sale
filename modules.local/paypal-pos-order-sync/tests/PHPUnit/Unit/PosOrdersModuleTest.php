<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PastSalesNotice;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\OrderSync\PosOrdersModule;
use Syde\PayPal\PointOfSale\OrderSync\Test\SetupInfoFake;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Actions\expectAdded;

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

    /**
     * WHEN onboarding completes
     * THEN the past sales notice is stored as pending, at priority 5
     */
    public function testOnboardingCompletionStoresThePastSalesNoticeAtPriorityFive(): void
    {
        $listeners = [];
        expectAdded('paypal-pos.onboarding.completed')->zeroOrMoreTimes()->whenHappen(
            static function (callable $listener, int $priority = 10) use (&$listeners): void {
                $listeners[$priority] = $listener;
            }
        );
        $setupInfo = new SetupInfoFake();
        $services = [
            'paypal-pos.order-sync.order-side-effects' => new OrderSideEffects(),
            'paypal-pos.setup-info' => $setupInfo->container(),
        ];
        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(static fn (string $id) => $services[$id]);

        (new PosOrdersModule())->run($container);
        $listeners[5]();

        self::assertSame([PastSalesNotice::PENDING_KEY], array_keys($setupInfo->values));
    }

    public function testResetAndUninstallDeleteTheImportOptions(): void
    {
        $options = (new PosOrdersModule())->extensions()['paypal-pos.onboarding.resettable.options'](['other']);

        self::assertSame(['other', PurchaseBackfill::STATUS_OPTION, PurchaseBackfill::WEBHOOK_GAP_OPTION], $options);
    }
}
