<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit;

use Psr\Log\NullLogger;
use Syde\PayPal\PointOfSale\Container\ArrayContainer;
use Syde\PayPal\PointOfSale\Logging\Logger\CompoundLogger;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

class ExtensionsTest extends MonkeryTestCase
{
    /**
     * Modules define their loggers as a NullLogger, so their logs are dropped unless the plugin
     * routes them into its own logger.
     */
    public function testOrderSyncLogsGoToThePluginLogger(): void
    {
        $extensions = require dirname(__DIR__, 3) . '/src/extensions.php';
        $pluginLogger = new CompoundLogger();
        $container = new ArrayContainer(['paypal-pos.logger' => $pluginLogger]);

        $logger = $extensions['paypal-pos.order-sync.logger'](new NullLogger(), $container);

        self::assertSame($pluginLogger, $logger);
    }
}
