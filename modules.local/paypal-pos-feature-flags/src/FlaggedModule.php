<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\FactoryModule;
use Inpsyde\Modularity\Module\Module;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

/**
 * A module that is active only while its feature flag is on. While the flag is off, it adds
 * no services, factories or extensions and its run() does nothing, so nothing else may depend
 * on its services.
 *
 * Modularity still lists it as added and executed under the module's ID. Uninstalling doesn't
 * activate it either, so data it stored while its flag was on stays behind.
 */
final class FlaggedModule implements ServiceModule, FactoryModule, ExtendingModule, ExecutableModule
{
    private readonly bool $on;

    public function __construct(Flags $flags, string $flag, private readonly Module $module)
    {
        // Once, so the services and run() agree even if a filter changes in between.
        $this->on = $flags->isOn($flag);
    }

    public function id(): string
    {
        return $this->module->id();
    }

    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return $this->on && $this->module instanceof ServiceModule ? $this->module->services() : [];
    }

    /**
     * @inheritDoc
     */
    public function factories(): array
    {
        return $this->on && $this->module instanceof FactoryModule ? $this->module->factories() : [];
    }

    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        return $this->on && $this->module instanceof ExtendingModule
            ? $this->module->extensions()
            : [];
    }

    public function run(ContainerInterface $container): bool
    {
        if (!$this->on || !$this->module instanceof ExecutableModule) {
            return true;
        }

        return $this->module->run($container);
    }
}
