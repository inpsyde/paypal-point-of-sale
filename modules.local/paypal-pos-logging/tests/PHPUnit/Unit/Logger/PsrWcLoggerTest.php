<?php
declare(strict_types=1); # -*- coding: utf-8 -*-
// phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType

namespace Syde\PayPal\PointOfSale\Logging\Logger\Tests\Unit;

use Syde\PayPal\PointOfSale\Logging\Logger\WooCommerceLogger;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\LoggerInterfaceTest;
use WC_Logger_Interface;

/**
 * Tests of a PSR-3 logger, most of the tests are in LoggerInterfaceTest
 */
class PsrWcLoggerTest extends LoggerInterfaceTest
{
    private $logs = [];

    /**
     * @inheritDoc
     */
    public function getLogger(): LoggerInterface
    {
        $wcLoggerMock = Mockery::mock(WC_Logger_Interface::class);
        $wcLoggerMock
            ->shouldReceive('log')
            ->andReturnUsing(function ($level, $message, array $context = []) {
                $this->logs[] = [$level, $message, $context];
            });

        return new WooCommerceLogger($wcLoggerMock);
    }

    function tearDown(): void
    {
        parent::tearDown();

        // fix no assertions warning for mockery-only tests
        $mockeryContainer = Mockery::getContainer();
        if ($mockeryContainer) {
            $this->addToAssertionCount($mockeryContainer->mockery_getExpectationCount());
        }
    }

    public function testEmptySource()
    {
        $wcLoggerMock = Mockery::mock(WC_Logger_Interface::class);
        $wcLoggerMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::any(), Mockery::any(), ['source' => 'paypal-point-of-sale']);

        $logger = new WooCommerceLogger($wcLoggerMock);

        $logger->info('hello', []);
    }

    public function testCustomSource()
    {
        $wcLoggerMock = Mockery::mock(WC_Logger_Interface::class);
        $source = 'MyProject';
        $wcLoggerMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::any(), Mockery::any(), ['source' => $source]);

        $logger = new WooCommerceLogger($wcLoggerMock);

        $logger->info('hello', ['source' => $source]);
    }

    public function testThrowsOnInvalidLevel()
    {
        $this->expectException('\Psr\Log\InvalidArgumentException');
        parent::testThrowsOnInvalidLevel();
    }

    /**
     * @inheritDoc
     */
    public function getLogs(): array
    {
        return array_map(function ($it) {
            return "$it[0] $it[1]";
        }, $this->logs);
    }
}
