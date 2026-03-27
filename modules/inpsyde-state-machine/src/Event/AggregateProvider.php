<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Psr\EventDispatcher\ListenerProviderInterface;
class AggregateProvider implements ListenerProviderInterface
{
    /**
     * @var array
     */
    protected $providers = [];
    /**
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     */
    public function getListenersForEvent(object $event): iterable
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
