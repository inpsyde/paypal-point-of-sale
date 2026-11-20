<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Handler\PurchaseCreatedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\WebhookHandler;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WebhookExtensionsTest extends BrainMonkeyWpTestCase
{
    public function testAddsThePurchaseCreatedHandlerAfterTheWebhookHandlers(): void
    {
        $existing = Mockery::mock(WebhookHandler::class);
        $handler = Mockery::mock(PurchaseCreatedHandler::class);
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with('paypal-pos.order-sync.purchase-created-handler')->andReturn($handler);
        $extensions = require dirname(__DIR__, 3) . '/extensions.php';

        self::assertSame([$existing, $handler], $extensions['paypal-pos.webhook.handlers']([$existing], $container));
    }
}
