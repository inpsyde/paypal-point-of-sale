<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Event\ListenerProvider;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Event\PostTransition;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Event\StateAwareListenerProvider;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Event\TransitionAwareListenerProvider;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Toggle;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\EnqueueProductSyncJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\WipeRemoteProductsJob;
return [
    'paypal-pos.init-possible' => static function (bool $previous, C $ctr): bool {
        $initableStates = [OnboardingState::SYNC_PARAM_PRODUCTS, OnboardingState::SYNC_PARAM_VAT, OnboardingState::SYNC_PROGRESS, OnboardingState::SYNC_FINISHED, OnboardingState::ONBOARDING_COMPLETED];
        $stateMachine = $ctr->get('inpsyde.state-machine');
        return in_array($stateMachine->currentState()->name(), $initableStates, \true);
    },
    'paypal-pos.sdk.dal.provider.organization' => static function (OrganizationProvider $previous, C $container): OrganizationProvider {
        $preSyncStates = $container->get('paypal-pos.onboarding.settings-states');
        $stateMachine = $container->get('inpsyde.state-machine');
        // clear cache, to always use the current account settings during setup steps
        if (in_array($stateMachine->currentState()->name(), $preSyncStates, \true)) {
            $container->get('paypal-pos.clear-cache')();
        }
        return $previous;
    },
    'inpsyde.state-machine.namespace' => static function (string $previous, C $container): string {
        return 'paypal-pos.onboarding';
    },
    'paypal-pos.settings.fields' => static function (array $previous, C $container): array {
        $filter = $container->get('paypal-pos.onboarding.settings.filter');
        return ['onboarding' => ['title' => __('Installation', 'paypal-point-of-sale'), 'type' => 'zettle-onboarding', 'description' => __('We will guide you through the initial configuration of the PayPal Point of Sale integration', 'paypal-point-of-sale'), 'desc_tip' => \true, 'default' => '']] + $filter->filter($previous);
    },
    'paypal-pos.settings.field-renderers' => static function (array $previous, C $container): array {
        $previous[] = $container->get('paypal-pos.onboarding.settings.renderer.removed');
        $previous[] = $container->get('paypal-pos.onboarding.settings.renderer.hidden');
        $previous[] = $container->get('paypal-pos.onboarding.settings.renderer.password');
        $previous[] = $container->get('paypal-pos.onboarding.settings.renderer.onboarding');
        return $previous;
    },
    'inpsyde.queue.rest.v1.endpoint.meta-callback' => static function (callable $previous, C $container): callable {
        return static function (array $meta, array $types = []) use ($container, $previous): array {
            $previous = $previous();
            if (!isset($meta['zettle-onboarding'])) {
                return $previous;
            }
            $phase = $meta['phase'];
            $jobRepo = $container->get('inpsyde.queue.repository');
            $jobTypes = ['prepare' => [EnqueueProductSyncJob::TYPE, WipeRemoteProductsJob::TYPE], 'sync' => [ExportProductJob::TYPE], 'cleanup' => []];
            $result = $jobRepo->fetch(1, $jobTypes[$phase]);
            return array_merge(['isFinished' => empty($result)], $previous);
        };
    },
    'inpsyde.state-machine.events.listener-provider.state-aware' => static function (StateAwareListenerProvider $listenerProvider, C $container): StateAwareListenerProvider {
        foreach ($container->get('paypal-pos.onboarding.state-machine.actions') as $sourceState => $listeners) {
            foreach ((array) $listeners as $listener) {
                $listenerProvider->listen($sourceState, $listener);
            }
        }
        return $listenerProvider;
    },
    'inpsyde.state-machine.events.listener-provider.transition-aware' => static function (TransitionAwareListenerProvider $listenerProvider, C $container): TransitionAwareListenerProvider {
        foreach ($container->get('paypal-pos.onboarding.state-machine.transition-events') as $transitionName => $listeners) {
            foreach ((array) $listeners as $listener) {
                $listenerProvider->listen($transitionName, $listener);
            }
        }
        return $listenerProvider;
    },
    'inpsyde.state-machine.events.listener-provider.internal' => static function (ListenerProvider $listenerProvider, C $container): ListenerProvider {
        $setState = $container->get('paypal-pos.onboarding.set-state');
        assert(is_callable($setState));
        $listenerProvider->addListener(static function (PostTransition $event) use ($setState): void {
            $setState($event->transition()->toState());
        });
        $listenerProvider->addListener($container->get('paypal-pos.onboarding.repository.auth-check-event'));
        $listenerProvider->addListener($container->get('paypal-pos.onboarding.repository.auth-failed-event'));
        $listenerProvider->addListener($container->get('paypal-pos.onboarding.repository.unhandled-error-event'));
        return $listenerProvider;
    },
    /**
     * We don't want the queue to process anything if onboarding has not yet completed.
     * In that case, we simply pass an empty array as the available queue runners
     */
    'inpsyde.queue.runners' => static function (array $previous, C $container): array {
        $currentState = $container->get('inpsyde.state-machine')->currentState()->name();
        if ($currentState === OnboardingState::ONBOARDING_COMPLETED) {
            return $previous;
        }
        return [];
    },
    'paypal-pos.assets.should-enqueue.all' => static function (callable $previous, C $container): callable {
        return static function () use ($previous, $container): bool {
            return $previous() and $container->get('paypal-pos.settings.is-integration-page')();
        };
    },
    'paypal-pos.assets.should-enqueue.sync-module' => static function (callable $previous, C $container): callable {
        return static function () use ($previous, $container): bool {
            if (!$previous()) {
                return \false;
            }
            $stateMachine = $container->get('inpsyde.state-machine');
            assert($stateMachine instanceof StateMachineInterface);
            return $stateMachine->currentState()->name() === OnboardingState::SYNC_PROGRESS;
        };
    },
    'inpsyde.wc-lifecycle-events.products.toggle' => static function (Toggle $toggle, C $container): Toggle {
        $currentState = $container->get('inpsyde.state-machine')->currentState()->name();
        if ($currentState === OnboardingState::ONBOARDING_COMPLETED) {
            return $toggle;
        }
        $toggle->disable();
        return $toggle;
    },
];
