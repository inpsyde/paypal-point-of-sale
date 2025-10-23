<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents\Event;

use Inpsyde\WcEvents\ParameterDeriver;
use InvalidArgumentException;
use WC_Product;

/**
 * This is pretty much a PSR-14 ListenerProvider with some advanced event registration methods.
 * It does not implement PSR-14 currently since we're tied to php7.2
 */
class ProductEventListenerRegistry
{

    /**
     * Used internally to mark a product as deleted.
     */
    public const DELETE_FLAG = '__mark_deleted';

    /**
     * @var array<callable(WC_Product,WC_Product):void> The full list of listeners
     */
    private $listeners = [];

    /**
     * @var ParameterDeriver
     */
    private $parameterDeriver;

    /**
     * ProductEventListenerRegistry constructor.
     *
     * @param ParameterDeriver $parameterDeriver
     */
    public function __construct(ParameterDeriver $parameterDeriver)
    {
        $this->parameterDeriver = $parameterDeriver;
    }

    /**
     * Inspects the event and returns appropriate listeners.
     *
     * @param ProductChangeEvent $event
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     *
     * @return array
     */
    public function getListenersForEvent(ProductChangeEvent $event): array
    {
        $result = [];
        foreach ($this->listeners as $listener) {
            if (!$this->shouldReturnListener($event->new(), $event->old(), $listener)) {
                continue;
            }
            $result[] = function (ProductChangeEvent $event) use ($listener) {
                ($this->createTypeMatchingGuard($listener))($event->new(), $event->old());
            };
        }

        return $result;
    }

    /**
     * Registers a list of listeners that fire whenever any change happens to a product.
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     */
    public function onChange(callable ...$callables): void
    {
        foreach ($callables as $callable) {
            $this->listeners[] = $callable;
        }
    }

    /**
     * Inspects the change event and fires listeners only if the specified property has changed
     *
     * @param string $property
     * @param array<callable(WC_Product,WC_Product):void>  ...$callables
     */
    public function onPropertyChange(string $property, callable ...$callables): void
    {
        foreach ($callables as $callable) {
            $this->listeners[] = $this->createPropertyGuard(
                $property,
                $this->createTypeMatchingGuard($callable)
            );
        }
    }

    /**
     * Inspects the change event and fires listeners only if a product has been
     * transitioned to the specified status.
     *
     * @param string $status
     * @param array<callable(WC_Product,WC_Product):void>  ...$callables
     */
    public function onStatusChange(string $status, callable ...$callables): void
    {
        foreach ($callables as $callable) {
            $this->listeners[] = $this->createStatusGuard($status, $this->createTypeMatchingGuard($callable));
        }
    }

    /**
     * Sugar for onStatusChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onPublish(callable ...$callables): void
    {
        $this->onStatusChange('publish', ...$callables);
    }

    /**
     * Sugar for onStatusChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onTrash(callable ...$callables): void
    {
        $this->onStatusChange('trash', ...$callables);
    }

    /**
     * Sugar for onStatusChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onDraft(callable ...$callables): void
    {
        $this->onStatusChange('draft', ...$callables);
    }

    /**
     * Sugar for onStatusChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onPending(callable ...$callables): void
    {
        $this->onStatusChange('pending', ...$callables);
    }

    /**
     * Sugar for onStatusChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onPrivate(callable ...$callables): void
    {
        $this->onStatusChange('private', ...$callables);
    }

    /**
     * Inspects the change event and fires listeners only if
     * catalog_visibility changed to the specified value.
     *
     * @param string $visibility
     * @param array<callable(WC_Product,WC_Product):void>  ...$callables
     */
    public function onCatalogVisibilityChange(string $visibility, callable ...$callables): void
    {
        foreach ($callables as $callable) {
            $this->listeners[] = $this->createCatalogVisibilityGuard(
                $visibility,
                $this->createTypeMatchingGuard($callable)
            );
        }
    }

    /**
     * Sugar for onCatalogVisibilityChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onHide(callable ...$callables): void
    {
        $this->onCatalogVisibilityChange('hidden', ...$callables);
    }

    /**
     * Sugar for onCatalogVisibilityChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onBecomeVisible(callable ...$callables): void
    {
        $this->onCatalogVisibilityChange('visible', ...$callables);
    }

    /**
     * Sugar for onCatalogVisibilityChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onBecomeSearchOnly(callable ...$callables): void
    {
        $this->onCatalogVisibilityChange('search', ...$callables);
    }

    /**
     * Sugar for onCatalogVisibilityChange
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     *
     * @see ProductEventListenerRegistry::onStatusChange()
     */
    public function onBecomeCatalogOnly(callable ...$callables): void
    {
        $this->onCatalogVisibilityChange('catalog', ...$callables);
    }

    /**
     * @param array<callable(WC_Product,WC_Product):void>  ...$callables
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function onDelete(callable ...$callables): void
    {
        foreach ($callables as $callable) {
            $this->listeners[] = function (WC_Product $new, WC_Product $old) use ($callable) {
                if ($new->get_meta(self::DELETE_FLAG) !== true) {
                    return;
                }
                $this->createTypeMatchingGuard($callable)($new, $old);
            };
        }
    }

    /**
     * Inspects the change event and fires listeners only if a product has been
     * turned into a different type of product.
     *
     * @param array<callable(WC_Product,WC_Product):void> ...$callables
     */
    public function onTypeChange(callable ...$callables): void
    {
        foreach ($callables as $callable) {
            $this->listeners[] = function (WC_Product $new, WC_Product $old) use ($callable) {
                if (get_class($old) === get_class($new)) {
                    return;
                }
                $this->createTypeMatchingGuard($callable)($new, $old);
            };
        }
    }

    /**
     * Wrap the given callback in a callable that inspects the callback and only
     * executes it if its parameters match the wrapper function's parameter types
     *
     * This will
     *
     * @param callable(WC_Product,WC_Product):void $callable
     *
     * @return callable(WC_Product,WC_Product):void
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    private function createTypeMatchingGuard(callable $callable): callable
    {
        return function (WC_Product $new, WC_Product $old) use ($callable) {
            $type1 = $this->parameterDeriver->parameterType($callable, 0);
            if (!$new instanceof $type1) {
                return;
            }
            try {
                /**
                 * Make the second parameter ( WC_Product $old ) optional
                 */
                $type2 = $this->parameterDeriver->parameterType($callable, 1);
                if (!$old instanceof $type2) {
                    return;
                }
                $callable($new, $old);
            } catch (InvalidArgumentException $exception) {
                /** @psalm-suppress TooFewArguments */
                $callable($new);
            }
        };
    }

    /**
     * Fires the $listener only if the specified property has changed.
     *
     * @param string $property
     * @param callable(WC_Product,WC_Product):void $callable
     *
     * @return callable(WC_Product,WC_Product):void
     */
    private function createPropertyGuard(string $property, callable $callable): callable
    {
        return static function (WC_Product $new, WC_Product $old) use ($property, $callable) {
            $method = 'get_' . $property;
            if (!method_exists($new, $method) && !method_exists($old, $method)) {
                return;
            }
            if (method_exists($new, $method) && !method_exists($old, $method)) {
                /**
                 * Method just became accessible due to a type transition.
                 */
                $callable($new, $old);

                return;
            }
            if ($old->$method() === $new->$method()) {
                return;
            }
            $callable($new, $old);
        };
    }

    /**
     * Fires the $listener only if the WC_Product has transitioned to the specified status.
     *
     * @param string $status
     * @param callable(WC_Product,WC_Product):void $callable
     *
     * @return callable(WC_Product,WC_Product)
     */
    private function createStatusGuard(string $status, callable $callable): callable
    {
        return static function (WC_Product $new, WC_Product $old) use ($status, $callable) {
            /**
             * Bail if new status does not match specified status
             */
            if ($new->get_status() !== $status) {
                return;
            }
            /**
             * Bail if old status is already teh specified one.
             * But ONLY IF the product already exists.
             * A new product could currently be created in the target status.
             * Then we still want the listener to be called.
             */
            if ($old->get_id() && $old->get_status() === $status) {
                return;
            }
            $callable($new, $old);
        };
    }

    /**
     * Fires the $listener only if catalog_visibility changed to the specified value.
     * Similar to createStatusGuard
     *
     * @param string $visibility
     * @param callable(WC_Product,WC_Product):void $callable
     *
     * @return callable(WC_Product,WC_Product)
     */
    private function createCatalogVisibilityGuard(string $visibility, callable $callable): callable
    {
        return static function (WC_Product $new, WC_Product $old) use ($visibility, $callable) {
            if ($new->get_catalog_visibility() !== $visibility) {
                return;
            }

            if ($old->get_id() && $old->get_catalog_visibility() === $visibility) {
                return;
            }
            $callable($new, $old);
        };
    }

    /**
     * Inspects the callback signature to determine if the callback can safely be called with the
     * WC_Product instances passed
     *
     * @param WC_Product $new
     * @param WC_Product $old
     * @param callable $listener The callback to inspect.
     *
     * @return bool
     */
    private function shouldReturnListener(WC_Product $new, WC_Product $old, callable $listener): bool
    {
        $param1 = $this->parameterDeriver->parameterType($listener, 0);
        if (!($new instanceof $param1)) {
            return false;
        }
        try {
            /**
             * This makes the second parameter ( WC_Product $old ) optional
             */
            $param2 = $this->parameterDeriver->parameterType($listener, 1);
            if (!($old instanceof $param2)) {
                return false;
            }
        } catch (InvalidArgumentException $exception) {
            return true;
        }

        return true;
    }
}
