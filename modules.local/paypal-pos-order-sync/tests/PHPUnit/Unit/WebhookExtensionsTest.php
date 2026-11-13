<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
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
}
