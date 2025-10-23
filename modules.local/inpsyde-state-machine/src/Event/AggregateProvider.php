<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Dhii\Events\Listener\ListenerProviderInterface;
use Traversable;

/**
 * This can be removed and replaced by fig/event-dispatcher-util's
 * implementation once the switch to PHP7.2 allows using PSR-14
 */
class AggregateProvider implements ListenerProviderInterface
{

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     * @param object $event
     *
     * @return Traversable
     */
    public function getListenersForEvent($event): Traversable
    {
        /** @var ListenerProviderInterface $provider */
        foreach ($this->providers as $provider) {
            yield from $provider->getListenersForEvent($event);
        }
        return yield from [];
    }

    /**
     * Enqueues a listener provider to this set.
     *
     * @param ListenerProviderInterface $provider
     *   The provider to add.
     *
     * @return AggregateProvider
     *   The called object.
     */
    public function addProvider(ListenerProviderInterface $provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }
}
