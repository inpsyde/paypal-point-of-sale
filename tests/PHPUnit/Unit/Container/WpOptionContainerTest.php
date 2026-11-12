<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit\Container;

use Syde\PayPal\PointOfSale\Container\WpOptionContainer;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

use function Brain\Monkey\Functions\when;

class WpOptionContainerTest extends MonkeryTestCase
{
    /**
     * @var array<string, mixed> Option rows in the table.
     */
    private array $stored = [];

    /**
     * @var array<string, mixed> What get_option() returns: this request's cached copy.
     */
    private array $cached = [];

    /**
     * @var array<string, mixed>
     */
    private array $alloptions = [];

    protected function setUp(): void
    {
        parent::setUp();
        when('get_option')->alias(function (string $name, $default = false) {
            // Like WP: the cached copy, or on a cache miss the table, which is then cached.
            if (!array_key_exists($name, $this->cached) && array_key_exists($name, $this->stored)) {
                $this->cached[$name] = $this->stored[$name];
            }

            return $this->cached[$name] ?? $default;
        });
        when('update_option')->alias(function (string $name, $value): bool {
            // Like WP: no write when the value equals the cached copy, or the table on a cache miss.
            $old = array_key_exists($name, $this->cached) ? $this->cached[$name] : ($this->stored[$name] ?? null);
            if ($value === $old) {
                return false;
            }
            $this->stored[$name] = $value;
            $this->cached[$name] = $value;

            return true;
        });
        when('wp_cache_delete')->alias(function (string $name): bool {
            unset($this->cached[$name]);

            return true;
        });
        when('wp_load_alloptions')->alias(fn (): array => $this->alloptions);
        when('wp_cache_set')->alias(function (string $key, array $value): bool {
            $this->alloptions = $value;

            return true;
        });
    }

    public function testWritesKeepKeysSavedSinceTheContainerWasCreated(): void
    {
        $this->stored['settings'] = ['order_sync_status' => 'wc-completed'];
        $this->cached = $this->stored;
        $container = new WpOptionContainer('settings');
        // Saved by another request: only the table has it, this request's option cache doesn't.
        $this->stored['settings'] = ['order_sync_status' => 'wc-processing', 'api_key' => 'key'];

        $container->set('webhook', 'listener');
        $container->unset('api_key');

        self::assertSame(['order_sync_status' => 'wc-processing', 'webhook' => 'listener'], $this->stored['settings']);
        self::assertSame('listener', $container->get('webhook'));
        self::assertFalse($container->has('api_key'));
    }

    public function testWritesEvenWhenTheResultMatchesTheStaleCache(): void
    {
        $this->stored['settings'] = [];
        $this->cached = $this->stored;
        $container = new WpOptionContainer('settings');
        $this->stored['settings'] = ['api_key' => 'key'];

        $container->unset('api_key');

        self::assertSame([], $this->stored['settings']);
    }

    public function testDropsAnAutoloadedOptionFromTheCacheBeforeWriting(): void
    {
        $this->stored['settings'] = [];
        $this->alloptions = ['settings' => 'stale', 'other' => 'kept'];

        (new WpOptionContainer('settings'))->set('webhook', 'listener');

        self::assertSame(['other' => 'kept'], $this->alloptions);
        self::assertSame(['webhook' => 'listener'], $this->stored['settings']);
    }

    public function testClearEmptiesTheContainerToo(): void
    {
        $this->stored['settings'] = ['webhook' => 'listener'];
        $this->cached = $this->stored;
        $container = new WpOptionContainer('settings');

        $container->clear();

        self::assertSame([], $this->stored['settings']);
        self::assertFalse($container->has('webhook'));
    }
}
