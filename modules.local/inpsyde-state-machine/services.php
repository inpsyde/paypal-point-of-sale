<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine;

use Dhii\Events\Listener\ListenerProviderInterface;
use Inpsyde\StateMachine\Event\AggregateProvider;
use Inpsyde\StateMachine\Event\EventDispatcher;
use Inpsyde\StateMachine\Event\ListenerProvider;
use Inpsyde\StateMachine\Event\StateAwareListenerProvider;
use Inpsyde\StateMachine\Event\TransitionAwareListenerProvider;
use Inpsyde\StateMachine\Guard\ContainerAwareGuard;
use Inpsyde\StateMachine\Guard\GuardInterface;
use Inpsyde\StateMachine\Initializer\ContainerInitializer;
use Inpsyde\StateMachine\Initializer\InitializerInterface;
use Inpsyde\StateMachine\Initializer\StateQueryInitializer;
use Inpsyde\StateMachine\Loader\ContainerLoader;
use Inpsyde\StateMachine\Loader\LoaderInterface;
use Psr\Container\ContainerInterface as C;

$wire = static function (string ...$parts): callable {
    $class = array_shift($parts);

    //phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
    return static function (C $container) use ($class, $parts) {
        return new $class(
            ...array_map(
                static function (string $key) use ($container) {
                    return $container->get($key);
                },
                $parts
            )
        );
    };
    //phpcs:enable
};
//phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
//phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
$scalar = static function ($thing): callable {
    return static function () use ($thing) {
        return $thing;
    };
};

//phpcs:enable

return [
    'inpsyde.state-machine.namespace' => $scalar('inpsyde'),
    'inpsyde.state-machine' => static function (C $container): StateMachineInterface {
        $stateMachine = $container->get('inpsyde.state-machine.implementation');
        $loader = $container->get('inpsyde.state-machine.loader');
        assert($loader instanceof LoaderInterface);

        return $loader->load($stateMachine);
    },
    'inpsyde.state-machine.implementation' => $wire(
        StateMachine::class,
        'inpsyde.state-machine.events'
    ),
    'inpsyde.state-machine.events.listener-provider.state-aware' =>
        static function (C $container): StateAwareListenerProvider {
            return new StateAwareListenerProvider();
        },
    'inpsyde.state-machine.events.listener-provider.transition-aware' =>
        static function (C $container): TransitionAwareListenerProvider {
            return new TransitionAwareListenerProvider();
        },
    'inpsyde.state-machine.events.listener-provider.internal' =>
        static function (C $container): ListenerProvider {
            return new ListenerProvider();
        },
    'inpsyde.state-machine.events.listener-provider' =>
        static function (C $container): ListenerProviderInterface {
            $provider = new AggregateProvider();
            $provider->addProvider($container->get('inpsyde.state-machine.events.listener-provider.internal'));
            $provider->addProvider(
                $container->get('inpsyde.state-machine.events.listener-provider.state-aware')
            );
            $provider->addProvider(
                $container->get('inpsyde.state-machine.events.listener-provider.transition-aware')
            );

            return $provider;
        },
    'inpsyde.state-machine.events' => static function (C $container): EventDispatcher {
        return new EventDispatcher(
            $container->get('inpsyde.state-machine.events.listener-provider')
        );
    },
    'inpsyde.state-machine.initializer.state-query' => $wire(StateQueryInitializer::class),
    'inpsyde.state-machine.initializer' => static function (C $container): InitializerInterface {
        return new ContainerInitializer(
            $container->get('inpsyde.state-machine.namespace'),
            $container,
            $container->get('inpsyde.state-machine.initializer.state-query')
        );
    },
    'inpsyde.state-machine.loader' => static function (C $container): LoaderInterface {
        return new ContainerLoader(
            $container->get('inpsyde.state-machine.namespace'),
            $container->get('inpsyde.state-machine.initializer'),
            $container
        );
    },
    'inpsyde.state-machine.guards.container-aware' =>
        static function (C $container): GuardInterface {
            return new ContainerAwareGuard(
                $container->get('inpsyde.state-machine.namespace'),
                $container
            );
        },
];
