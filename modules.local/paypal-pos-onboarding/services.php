<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding;

use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Onboarding\Cli\ResetOnboardingCommand;
use Syde\PayPal\PointOfSale\Onboarding\Comparison\StoreComparison;
use Syde\PayPal\PointOfSale\Onboarding\Counter\ProductSyncJobsCounter;
use Syde\PayPal\PointOfSale\Onboarding\DataProvider\Store\StoreDataProvider;
use Syde\PayPal\PointOfSale\Onboarding\DataProvider\Store\WooCommerceStoreDataProvider;
use Syde\PayPal\PointOfSale\Onboarding\DataProvider\Store\ZettleStoreDataProvider;
use Syde\PayPal\PointOfSale\Onboarding\Job\ResetOnboardingJob;
use Syde\PayPal\PointOfSale\Onboarding\Listener\UnhandledErrorListener;
use Syde\PayPal\PointOfSale\Onboarding\Provider\ErrorListenerProvider;
use Syde\PayPal\PointOfSale\Onboarding\Provider\OnboardingRenderProvider;
use Syde\PayPal\PointOfSale\Onboarding\Provider\ResetCommandProvider;
use Syde\PayPal\PointOfSale\Onboarding\Provider\StateMachineProvider;
use Syde\PayPal\PointOfSale\Onboarding\Rest\DisconnectEndpoint;
use Syde\PayPal\PointOfSale\Onboarding\Rest\EndpointInterface;
use Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer\HiddenFieldRenderer;
use Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer\OnboardingFieldRenderer;
use Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer\RemovedFieldRenderer;
use Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer\WriteOnlyPasswordFieldRenderer;
use Syde\PayPal\PointOfSale\Onboarding\Settings\Filter\GenericSettingsValueFilter;
use Syde\PayPal\PointOfSale\Onboarding\Settings\Filter\OnboardingProcessFilter;
use Syde\PayPal\PointOfSale\Onboarding\Settings\Filter\SettingsFilter;
use Syde\PayPal\PointOfSale\Onboarding\Settings\Filter\SettingsValueFilter;
use Syde\PayPal\PointOfSale\Onboarding\Settings\OnboardingStepper;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\ContainerAwareView;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\OnboardingCompletedView;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\OnboardingView;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\ProductSyncParamView;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\SyncProgressView;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\SyncVatParamView;
use Syde\PayPal\PointOfSale\Onboarding\Settings\WriteOnlyPasswordFieldChecker;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Syde\PayPal\PointOfSale\Provider;
use Syde\PayPal\PointOfSale\Settings\FieldRenderer\FieldRendererInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use WC_Admin_Settings;
use wpdb;

// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong

$job = static function (string $type): string {
    return "paypal-pos.job.{$type}";
};

return [
    'paypal-pos.onboarding.wpdb' => static function (C $container): wpdb {
        global $wpdb;

        return $wpdb;
    },
    'paypal-pos.onboarding.option.state' => static function (C $container): string {
        return 'onboarding.current-state';
    },
    'paypal-pos.onboarding.initial-state' => static function (C $container): string {
        $optionContainer = $container->get('paypal-pos.settings');
        $key = $container->get('paypal-pos.onboarding.option.state');
        if (!$optionContainer->has($key)) {
            return '';
        }

        return $optionContainer->get($key);
    },
    'paypal-pos.onboarding.set-state' => static function (C $container): callable {
        return static function (string $state) use ($container) {
            $container->get('paypal-pos.settings')->set(
                $container->get('paypal-pos.onboarding.option.state'),
                $state
            );
        };
    },
    'paypal-pos.onboarding.current-state' => static function (C $container): string {
        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        return $stateMachine->currentState()->name();
    },
    'paypal-pos.onboarding.comparison.store.remote' =>
        static function (C $container): StoreDataProvider {
            return new ZettleStoreDataProvider(
                $container->get('paypal-pos.sdk.dal.provider.organization')
            );
        },
    'paypal-pos.onboarding.comparison.store.local' =>
        static function (C $container): StoreDataProvider {
            return new WooCommerceStoreDataProvider(
                $container->get('paypal-pos.wc.shop.location'),
                $container->get('paypal-pos.wc.tax.standard-rates')
            );
        },
    'paypal-pos.onboarding.comparison.store' => static function (C $container): StoreComparison {
        return new StoreComparison(
            $container->get('paypal-pos.onboarding.comparison.store.remote'),
            $container->get('paypal-pos.onboarding.comparison.store.local')
        );
    },
    'paypal-pos.onboarding.zettle-link' => static function (C $container): array {
        return [
            'title' => __('Zettle.com', 'paypal-point-of-sale'),
            'url' => __('https://zettle.com/', 'paypal-point-of-sale'),
        ];
    },
    'paypal-pos.onboarding.documentation-link' => static function (C $container): array {
        return [
            'title' => __('Documentation - Supported Product Types', 'paypal-point-of-sale'),
            'url' => __(
                'https://woocommerce.com/document/paypal-zettle-pos-for-woocommerce/#what-are-the-requirements-for-woocommerce-products-to-be-synchronized-with-paypal-zettle',
                'paypal-point-of-sale'
            ),
        ];
    },
    'paypal-pos.onboarding.zettle-products-link' => static function (C $container): array {
        return [
            'title' => __('Go to PayPal Point of Sale product library', 'paypal-point-of-sale'),
            'url' => __('https://my.zettle.com/products', 'paypal-point-of-sale'),
        ];
    },
    'paypal-pos.onboarding.support-link' => static function (C $container): array {
        return [
            'title' => __('PayPal Point of Sale Support', 'paypal-point-of-sale'),
            'url' => __('https://zettle.com/', 'paypal-point-of-sale'),
        ];
    },
    'paypal-pos.onboarding.full-settings-link' => static function (C $container): array {
        return [
            'title' => __('Show settings', 'paypal-point-of-sale'),
            'url' => add_query_arg(
                [
                    'review' => true,
                ],
                $container->get('paypal-pos.settings.url')
            ),
        ];
    },
    'paypal-pos.onboarding.error.message.webhooks' => static function (C $container): string {
        return __(
            'Registration of PayPal Point of Sale webhooks has failed, product stock changes from PayPal Point of Sale will not apply.
            Please check WC logs and reach out the support.
            You can repeat webhooks registration by deactivating and activating the plugin in the plugins list.',
            'paypal-point-of-sale'
        );
    },
    'paypal-pos.onboarding.settings.view.product-sync-params' =>
        static function (C $container): OnboardingView {
            return new ProductSyncParamView(
                $container->get('paypal-pos.onboarding.count.products.local'),
                $container->get('paypal-pos.onboarding.count.products.local.total'),
                $container->get('paypal-pos.onboarding.count.products.remote'),
                $container->get('paypal-pos.onboarding.documentation-link')
            );
        },
    'paypal-pos.onboarding.settings.view.sync-vat-param' =>
        static function (C $container): OnboardingView {
            return new SyncVatParamView(
                $container->get('paypal-pos.onboarding.comparison.store'),
                $container->get('paypal-pos.onboarding.comparison.store.remote'),
                $container->get('paypal-pos.onboarding.comparison.store.local'),
                $container->get('paypal-pos.sdk.default-taxes')
            );
        },
    'paypal-pos.onboarding.settings.view.sync-progress' =>
        static function (C $container): OnboardingView {
            return new SyncProgressView(
                $container->get('paypal-pos.onboarding.count.products.local'),
                $container->get('paypal-pos.onboarding.count.products.local.total'),
                $container->get('paypal-pos.settings.wc-integration.title')
            );
        },
    'paypal-pos.onboarding.settings.view.onboarding-completed' =>
        static function (C $container): OnboardingView {
            return new OnboardingCompletedView(
                $container->get('paypal-pos.onboarding.zettle-products-link'),
                $container->get('paypal-pos.onboarding.full-settings-link')
            );
        },
    'paypal-pos.onboarding.settings.renderer.onboarding.current' =>
        static function (C $container): OnboardingView {
            return new ContainerAwareView($container);
        },
    'paypal-pos.onboarding.settings.renderer.hidden' =>
        static function (C $container): FieldRendererInterface {
            return new HiddenFieldRenderer();
        },
    'paypal-pos.onboarding.settings.renderer.removed' =>
        static function (C $container): FieldRendererInterface {
            return new RemovedFieldRenderer();
        },
    'paypal-pos.onboarding.settings.renderer.password' =>
        static function (C $container): FieldRendererInterface {
            return new WriteOnlyPasswordFieldRenderer();
        },

    'paypal-pos.onboarding.settings.stepper.exclude' => static function (C $container): array {
        return [
            OnboardingState::WELCOME,
            OnboardingState::INVALID_CREDENTIALS,
            OnboardingState::SYNC_FINISHED,
            OnboardingState::ONBOARDING_COMPLETED,
        ];
    },
    'paypal-pos.onboarding.settings.stepper' => static function (C $container): OnboardingStepper {
        return new OnboardingStepper(
            $container->get('paypal-pos.onboarding.states'),
            $container->get('inpsyde.state-machine')->currentState()->name(),
            $container->get('paypal-pos.onboarding.settings.stepper.exclude'),
            __('Step', 'paypal-point-of-sale')
        );
    },
    'paypal-pos.onboarding.settings.renderer.onboarding' =>
        static function (C $container): FieldRendererInterface {
            $stateMachine = $container->get('inpsyde.state-machine');

            return new OnboardingFieldRenderer(
                $stateMachine->currentState()->name(),
                $container->get('paypal-pos.onboarding.settings.renderer.onboarding.current'),
                $container->get('paypal-pos.onboarding.settings.stepper'),
                $container->get('paypal-pos.settings.is-integration-page')
            );
        },
    'paypal-pos.onboarding.settings.filter' => static function (
        C $container
    ): SettingsFilter {
        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        return new OnboardingProcessFilter(
            $stateMachine->currentState()->name()
        );
    },
    'paypal-pos.onboarding.settings.write-only-password-field-checker' => static function (
        C $container
    ): callable {
        return new WriteOnlyPasswordFieldChecker(
            $container->get('paypal-pos.onboarding.settings.write-only-password-field-checker.placeholder.char'),
            $container->get('paypal-pos.onboarding.settings.write-only-password-field-checker.placeholder.max-length')
        );
    },
    'paypal-pos.onboarding.settings.write-only-password-field-checker.placeholder.char' => static function (
        C $container
    ): string {
        return '*';
    },
    'paypal-pos.onboarding.settings.write-only-password-field-checker.placeholder.max-length' => static function (
        C $container
    ): int {
        return 15;
    },
    'paypal-pos.onboarding.settings.value-filter.api-key' => static function (
        C $container
    ): SettingsValueFilter {
        return new GenericSettingsValueFilter(
            'api_key',
            $container->get('paypal-pos.onboarding.settings.write-only-password-field-checker')
        );
    },
    'paypal-pos.onboarding.pre-auth-states' => static function (C $container): array {
        return [
            OnboardingState::WELCOME,
            OnboardingState::API_CREDENTIALS,
            OnboardingState::INVALID_CREDENTIALS,
        ];
    },
    'paypal-pos.onboarding.no-auth-states' => static function (C $container): array {
        return array_merge(
            $container->get('paypal-pos.onboarding.pre-auth-states'),
            [
                OnboardingState::UNHANDLED_ERROR,
            ]
        );
    },
    'paypal-pos.onboarding.api-auth-check' => static function (C $container): callable {
        return static function () use ($container): bool {
            $notAllowedStates = (array) $container->get('paypal-pos.onboarding.no-auth-states');

            $stateMachine = $container->get('inpsyde.state-machine');
            assert($stateMachine instanceof StateMachineInterface);

            if (in_array($stateMachine->currentState()->name(), $notAllowedStates, true)) {
                return false;
            }

            return $container->get('paypal-pos.sdk.api.auth-check')();
        };
    },
    'paypal-pos.onboarding.auth.failure.not-allowed-states' => static function (C $container): array {
        return array_merge(
            $container->get('paypal-pos.onboarding.no-auth-states'),
            [
                OnboardingState::SYNC_FINISHED, // doesn't make sense to go back after sync
                OnboardingState::ONBOARDING_COMPLETED,
            ]
        );
    },
    'paypal-pos.onboarding.failure.excluded-states' => static function (C $container): array {
        return [
            OnboardingState::ONBOARDING_COMPLETED,
            OnboardingState::UNHANDLED_ERROR,
        ];
    },
    'paypal-pos.onboarding.failure.listener' =>
        static function (C $container): UnhandledErrorListener {
            return new UnhandledErrorListener(
                $container->get('inpsyde.state-machine'),
                $container->get('paypal-pos.http.page-reloader'),
                $container->get('paypal-pos.throw-unhandled-errors')
            );
        },
    'paypal-pos.onboarding.settings-states' => static function (): array {
        return [
            OnboardingState::API_CREDENTIALS,
            OnboardingState::SYNC_PARAM_PRODUCTS,
            OnboardingState::SYNC_PARAM_VAT,
        ];
    },
    'paypal-pos.onboarding.collector.products.local' => static function (C $container): callable {
        return static function (array $types = ['simple', 'variable']) use ($container): array {
            $repository = $container->get('paypal-pos.sdk.repository.woocommerce.product');
            assert($repository instanceof WcProductRepositoryInterface);

            $products = $repository->fetchFromTypes($types);

            $isSyncable = $container->get('paypal-pos.sync.product.sync-active-for-id');
            assert(is_callable($isSyncable));

            return array_filter($products, $isSyncable);
        };
    },
    'paypal-pos.onboarding.count.products.local' => static function (C $container): callable {
        return static function () use ($container): int {
            return count($container->get('paypal-pos.onboarding.collector.products.local')());
        };
    },
    'paypal-pos.onboarding.collector.products.local.total' =>
        static function (C $container): callable {
            return static function () use ($container): array {
                $repository = $container->get('paypal-pos.sdk.repository.woocommerce.product');
                assert($repository instanceof WcProductRepositoryInterface);

                return $repository->fetch();
            };
        },
    'paypal-pos.onboarding.count.products.local.total' => static function (C $container): callable {
        return static function () use ($container): int {
            return count($container->get('paypal-pos.onboarding.collector.products.local.total')());
        };
    },
    'paypal-pos.onboarding.collector.products.remote' => static function (C $container): array {
        $products = $container->get('paypal-pos.sdk.api.products');
        assert($products instanceof Products);

        try {
            $productCollection = $products->list();

            return $productCollection->all();
        } catch (ZettleRestException $exception) {
            return [];
        }
    },
    'paypal-pos.onboarding.count.products.remote' => static function (C $container): callable {
        return static function () use ($container): int {
            return count($container->get('paypal-pos.onboarding.collector.products.remote'));
        };
    },
    'paypal-pos.onboarding.counter.product.sync' =>
        static function (C $container): ProductSyncJobsCounter {
            $settings = $container->get('paypal-pos.settings');
            $syncStrategy = 'add';

            if ($settings->has('sync_collision_strategy')) {
                $syncStrategy = $settings->get('sync_collision_strategy');
            }

            return new ProductSyncJobsCounter(
                $container->get('paypal-pos.onboarding.collector.products.local'),
                $syncStrategy
            );
        },
    'paypal-pos.onboarding.message.add.error' => static function (C $container): callable {
        return static function (string $message): void {
            WC_Admin_Settings::add_error($message);
        };
    },
    'paypal-pos.onboarding.resettable.options' => static function (C $container): array {
        return [
            $container->get('paypal-pos.onboarding.option.state'),
            $container->get('paypal-pos.webhook.storage.option'),
            $container->get('paypal-pos.sdk.option.integration'),
            $container->get('paypal-pos.auth.is-failed.key'),
        ];
    },
    'paypal-pos.onboarding.resettable.transients' => static function (C $container): array {
        return [
            $container->get('paypal-pos.sdk.dal.provider.organization.transient-key'),
        ];
    },
    'paypal-pos.onboarding.resettable.tables' => static function (C $container): array {
        $idMap = $container->get('paypal-pos.sdk.dal.table');
        $queueTable = $container->get('inpsyde.queue.table');

        return [
            $idMap->name(),
            $queueTable->name(),
        ];
    },
    $job(ResetOnboardingJob::TYPE) => static function (C $container): Job {
        return new ResetOnboardingJob(
            $container->get('paypal-pos.onboarding.wpdb'),
            $container->get('paypal-pos.settings'),
            $container->get('paypal-pos.setup-info'),
            $container->get('paypal-pos.oauth.token-storage'),
            $container->get('paypal-pos.onboarding.resettable.tables'),
            $container->get('paypal-pos.onboarding.resettable.transients'),
            $container->get('paypal-pos.onboarding.resettable.options'),
            $container->get('paypal-pos.webhook.delete')
        );
    },
    'paypal-pos.onboarding.cli.reset.onboarding' =>
        static function (C $container) use ($job): ResetOnboardingCommand {
            return new ResetOnboardingCommand(
                $container->get($job(ResetOnboardingJob::TYPE)),
                $container->get('paypal-pos.is-multisite'),
                $container->get('paypal-pos.current-site-id'),
                $container->get('inpsyde.queue.logger')
            );
        },
    'paypal-pos.onboarding.provider.state-machine' => static function (C $container): Provider {
        return new StateMachineProvider(
            $container->get('inpsyde.state-machine')
        );
    },
    'paypal-pos.onboarding.provider.cli.command.reset' => static function (C $container): Provider {
        return new ResetCommandProvider(
            $container->get('paypal-pos.onboarding.cli.reset.onboarding')
        );
    },
    'paypal-pos.onboarding.provider.listener.error' => static function (C $container): Provider {
        return new ErrorListenerProvider(
            $container->get('paypal-pos.onboarding.failure.listener')
        );
    },
    'paypal-pos.onboarding.provider.render' => static function (C $container): Provider {
        return new OnboardingRenderProvider(
            $container->get('inpsyde.state-machine')
        );
    },
    'paypal-pos.onboarding.provider' => static function (C $container): array {
        return [
            $container->get('paypal-pos.onboarding.provider.state-machine'),
            $container->get('paypal-pos.onboarding.provider.listener.error'),
            $container->get('paypal-pos.onboarding.provider.render'),
            $container->get('paypal-pos.onboarding.provider.cli.command.reset'),
        ];
    },

    'paypal-pos.onboarding.rest.namespace' => static function (): string {
        return "zettle-onboarding/v1";
    },
    'paypal-pos.onboarding.disconnect.endpoint' => static function (C $container): EndpointInterface {
        return new DisconnectEndpoint(
            $container->get('paypal-pos.job.' . ResetOnboardingJob::TYPE),
            $container->get('paypal-pos.logger')
        );
    },
    'paypal-pos.onboarding.disconnect.endpoint.url' => static function (C $container): string {
        $endpoint = $container->get('paypal-pos.onboarding.disconnect.endpoint');
        return rest_url(
            $container->get('paypal-pos.onboarding.rest.namespace') . $endpoint->route()
        );
    },

    'paypal-pos.onboarding.first-import-timestamp' => static function (C $container): ?int {
        $setupInfo = $container->get('paypal-pos.setup-info');
        if ($setupInfo->has('first_import_timestamp')) {
            return $setupInfo->get('first_import_timestamp');
        }

        return $container->get('paypal-pos.plugin.properties')->lastUpdateTimestamp();
    },
];
