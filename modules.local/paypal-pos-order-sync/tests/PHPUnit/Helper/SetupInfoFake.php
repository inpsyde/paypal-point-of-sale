<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync\Test;

use Mockery;
use Mockery\MockInterface;
use Syde\PayPal\PointOfSale\Container\WritableContainerInterface;

/**
 * In-memory stand-in for the setup info container.
 *
 * Not an implementation of the interface: it lives in the root plugin and is not autoloadable in
 * this module's test run, so Mockery fabricates it.
 */
final class SetupInfoFake
{
    /**
     * @var array<string, mixed>
     */
    public array $values;

    public int $unsetCalls = 0;

    private MockInterface $container;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;

        $this->container = Mockery::mock(WritableContainerInterface::class);
        $this->container->shouldReceive('has')
            ->andReturnUsing(fn (string $key): bool => isset($this->values[$key]));
        $this->container->shouldReceive('get')
            ->andReturnUsing(fn (string $key) => $this->values[$key]);
        $this->container->shouldReceive('set')
            ->andReturnUsing(function (string $key, $value): void {
                $this->values[$key] = $value;
            });
        $this->container->shouldReceive('unset')
            ->andReturnUsing(function (string $key): void {
                $this->unsetCalls++;
                unset($this->values[$key]);
            });
    }

    public function container(): WritableContainerInterface
    {
        /** @var WritableContainerInterface */
        return $this->container;
    }

    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }
}
