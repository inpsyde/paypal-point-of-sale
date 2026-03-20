<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcEvents;

use Syde\Vendor\Zettle\Inpsyde\WcEvents\Event\EventDispatcher;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Hooks\ProductHooks;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return ['inpsyde.wc-lifecycle-events.products.hooks' => static function (C $container): ProductHooks {
    return new ProductHooks($container->get('inpsyde.wc-lifecycle-events.event-dispatcher'), $container->get('inpsyde.wc-lifecycle-events.products.toggle'), $container->get('inpsyde.wc-lifecycle-events.dispatch-decider'));
}, 'inpsyde.wc-lifecycle-events.products.listener-provider' => static function (C $container): ProductEventListenerRegistry {
    return new ProductEventListenerRegistry($container->get('inpsyde.wc-lifecycle-events.parameter-deriver'));
}, 'inpsyde.wc-lifecycle-events.event-dispatcher' => static function (C $container): EventDispatcher {
    return new EventDispatcher($container->get('inpsyde.wc-lifecycle-events.products.listener-provider'));
}, 'inpsyde.wc-lifecycle-events.products.toggle' => static function (C $container): Toggle {
    return new Toggle();
}, 'inpsyde.wc-lifecycle-events.dispatch-decider' => static function (C $container): DispatchDecider {
    return new DispatchDecider(...$container->get('inpsyde.wc-lifecycle-events.dispatch-deciders'));
}, 'inpsyde.wc-lifecycle-events.dispatch-deciders' => static function (C $container): array {
    return [];
}, 'inpsyde.wc-lifecycle-events.parameter-deriver' => static function (C $container): ParameterDeriver {
    return new ParameterDeriver();
}];
