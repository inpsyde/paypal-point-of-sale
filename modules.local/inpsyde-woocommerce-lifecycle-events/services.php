<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Inpsyde\WcEvents\Event\EventDispatcher;
use Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Inpsyde\WcEvents\Hooks\ProductHooks;
use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.wc-lifecycle-events.products.hooks' => static function (C $container): ProductHooks {
        return new ProductHooks(
            $container->get('inpsyde.wc-lifecycle-events.event-dispatcher'),
            $container->get('inpsyde.wc-lifecycle-events.products.toggle'),
            $container->get('inpsyde.wc-lifecycle-events.dispatch-decider')
        );
    },
    'inpsyde.wc-lifecycle-events.products.listener-provider' =>
        static function (C $container): ProductEventListenerRegistry {
            return new ProductEventListenerRegistry(
                $container->get('inpsyde.wc-lifecycle-events.parameter-deriver')
            );
        },
    'inpsyde.wc-lifecycle-events.event-dispatcher' =>
        static function (C $container): EventDispatcher {
            return new EventDispatcher(
                $container->get('inpsyde.wc-lifecycle-events.products.listener-provider')
            );
        },
    'inpsyde.wc-lifecycle-events.products.toggle' => static function (C $container): Toggle {
        return new Toggle();
    },
    'inpsyde.wc-lifecycle-events.dispatch-decider' =>
        static function (C $container): DispatchDecider {
            return new DispatchDecider(...$container->get('inpsyde.wc-lifecycle-events.dispatch-deciders'));
        },
    'inpsyde.wc-lifecycle-events.dispatch-deciders' => static function (C $container): array {
        return [];
    },
    'inpsyde.wc-lifecycle-events.parameter-deriver' =>
        static function (C $container): ParameterDeriver {
            return new ParameterDeriver();
        },
];
