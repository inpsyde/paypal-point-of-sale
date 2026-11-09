<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit\Container;

use Syde\PayPal\PointOfSale\Container\WpOptionContainer;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

use function Brain\Monkey\Functions\when;

class WpOptionContainerTest extends MonkeryTestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $stored = [];

    protected function setUp(): void
    {
        parent::setUp();
        when('get_option')->alias(fn (string $name, $default = false) => $this->stored[$name] ?? $default);
        when('update_option')->alias(function (string $name, $value): bool {
            $this->stored[$name] = $value;

            return true;
        });
    }

    public function testWritesKeepKeysSavedSinceTheContainerWasCreated(): void
    {
        $this->stored['settings'] = ['order_sync_status' => 'wc-completed'];
        $container = new WpOptionContainer('settings');
        $this->stored['settings'] = ['order_sync_status' => 'wc-processing', 'api_key' => 'key'];

        $container->set('webhook', 'listener');
        $container->unset('api_key');

        self::assertSame(['order_sync_status' => 'wc-processing', 'webhook' => 'listener'], $this->stored['settings']);
        self::assertSame('listener', $container->get('webhook'));
        self::assertFalse($container->has('api_key'));
    }

    public function testClearEmptiesTheContainerToo(): void
    {
        $this->stored['settings'] = ['webhook' => 'listener'];
        $container = new WpOptionContainer('settings');

        $container->clear();

        self::assertSame([], $this->stored['settings']);
        self::assertFalse($container->has('webhook'));
    }
}
