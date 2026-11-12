<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Handler\PurchaseCreatedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\WebhookHandler;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WebhookExtensionsTest extends BrainMonkeyWpTestCase
{
    /**
     * @return array<string, callable>
     */
    private function extensions(): array
    {
        return require dirname(__DIR__, 3) . '/extensions.php';
    }

    private function container(string $id, object $service): ContainerInterface
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with($id)->andReturn($service);

        return $container;
    }

    public function testAddsThePurchaseCreatedHandlerAfterTheWebhookHandlers(): void
    {
        $existing = Mockery::mock(WebhookHandler::class);
        $handler = Mockery::mock(PurchaseCreatedHandler::class);

        $handlers = $this->extensions()['paypal-pos.webhook.handlers'](
            [$existing],
            $this->container('paypal-pos.order-sync.purchase-created-handler', $handler)
        );

        self::assertSame([$existing, $handler], $handlers);
    }

    public function testRemembersTheGapWhenTheRegistrationFails(): void
    {
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldReceive('rememberWebhookGap')->once();
        $previousCalled = false;

        $onFailed = $this->extensions()['paypal-pos.webhook.on-registration-failed'](
            static function () use (&$previousCalled): void {
                $previousCalled = true;
            },
            $this->container('paypal-pos.order-sync.backfill', $backfill)
        );
        $onFailed();

        self::assertTrue($previousCalled);
    }

    public function testRecoversTheGapOnceTheWebhookIsRegistered(): void
    {
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldReceive('recoverWebhookGap')->once();
        $previousCalled = false;

        $onRegistered = $this->extensions()['paypal-pos.webhook.on-registered'](
            static function () use (&$previousCalled): void {
                $previousCalled = true;
            },
            $this->container('paypal-pos.order-sync.backfill', $backfill)
        );
        $onRegistered();

        self::assertTrue($previousCalled);
    }
}
