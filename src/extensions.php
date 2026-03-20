<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale;

use Exception;
use Inpsyde\Debug\DebugProxyFactory;
use Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\PayPal\PointOfSale\PhpSdk\Psr18RestClient;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Throwable;

return [
    'inpsyde.debug.logger' => static function (
        LoggerInterface $previous,
        C $container
    ): LoggerInterface {
        return $container->get('paypal-pos.logger')->addLogger($previous);
    },
    'inpsyde.queue.logger' => static function (
        LoggerInterface $previous,
        C $container
    ): LoggerInterface {
        return $container->get('paypal-pos.logger')->addLogger($previous);
    },
    'paypal-pos.webhook.logger' => static function (
        LoggerInterface $previous,
        C $container
    ): LoggerInterface {
        return $container->get('paypal-pos.logger')->addLogger($previous);
    },
    /**
     * Wire up the Zettle Settings module to the Auth module
     * by passing the SettingsContainer into the CredentialsContainer
     */
    'paypal-pos.oauth.credentials.parent' => static function (
        ?C $previous,
        C $container
    ): C {
        return $container->get('paypal-pos.settings');
    },
    'paypal-pos.sdk.rest-client' =>
        static function (Psr18RestClient $client, C $container): Psr18RestClient {
            $proxyFactory = $container->get('inpsyde.debug.proxy-factory');
            assert($proxyFactory instanceof DebugProxyFactory);

            return $proxyFactory->forInstanceMethods($client);
        },
    'inpsyde.queue.exception-handler' =>
        static function (callable $previous, C $container): callable {
            return static function (Throwable $exception) use ($previous, $container) {
                $previous($exception);
                $container->get('inpsyde.debug.exception-handler')->handle($exception);
            };
        },

    'inpsyde.wc-status-report.items' => static function (array $items, C $container): array {
        $factory = $container->get('inpsyde.wc-status-report.item-factory');
        assert($factory instanceof ReportItemFactoryInterface);

        $settings = $container->get('paypal-pos.settings');
        assert($settings instanceof C);

        $state = $settings->has('onboarding.current-state') ? $settings->get('onboarding.current-state') : '';

        $items[] = $factory->createReportItem(
            __('Onboarding state', 'paypal-point-of-sale'),
            'Onboarding state',
            $state
        );

        $preAuthStates = $container->get('paypal-pos.onboarding.pre-auth-states');
        if (!$state || in_array($state, $preAuthStates, true)) {
            return $items;
        }

        $items[] = $factory->createReportItem(
            __('Price sync', 'paypal-point-of-sale'),
            'Price sync',
            $container->get('paypal-pos.sync.price-sync-enabled') ? 'yes' : 'no'
        );
        $items[] = $factory->createReportItem(
            __('Initial sync collision strategy', 'paypal-point-of-sale'),
            'Initial sync collision strategy',
            $settings->has('sync_collision_strategy') ? $settings->get('sync_collision_strategy') : ''
        );

        $firstImportTimestamp = $container->get('paypal-pos.onboarding.first-import-timestamp');
        if ($firstImportTimestamp) {
            $items[] = $factory->createReportItem(
                __('First import', 'paypal-point-of-sale'),
                'First import',
                $container->get('paypal-pos.format-timestamp')($firstImportTimestamp)
            );
        }

        $productCounter = $container->get('paypal-pos.sdk.id-map.product');
        try {
            $items[] = $factory->createReportItem(
                __('Number of products syncing', 'paypal-point-of-sale'),
                'Number of products syncing',
                $productCounter->count()
            );
        } catch (Exception $exc) {
            $container->get('paypal-pos.logger')
                ->warning(sprintf('Failed to load number of products syncing: %1$s', $exc->getMessage()));
        }

        try {
            $org = $container->get('paypal-pos.sdk.dal.provider.organization')->provide();
            assert($org instanceof Organization);

            if ($org->vat()) {
                $items[] = $factory->createReportItem(
                    __('PayPal Point of Sale VAT', 'paypal-point-of-sale'),
                    'PayPal Point of Sale VAT',
                    $org->vat()->percentage()
                );
            }
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale currency', 'paypal-point-of-sale'),
                'PayPal Point of Sale currency',
                $org->currency()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale country', 'paypal-point-of-sale'),
                'PayPal Point of Sale country',
                $org->country()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale language', 'paypal-point-of-sale'),
                'PayPal Point of Sale language',
                $org->language()
            );
            $timezone = $org->timeZone();
            if ($timezone) {
                $items[] = $factory->createReportItem(
                    __('PayPal Point of Sale time zone', 'paypal-point-of-sale'),
                    'PayPal Point of Sale time zone',
                    $timezone->getName()
                );
            }
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale contact email', 'paypal-point-of-sale'),
                'PayPal Point of Sale contact email',
                $org->contactEmail()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale taxation mode', 'paypal-point-of-sale'),
                'PayPal Point of Sale taxation mode',
                $org->taxationMode()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale taxation type', 'paypal-point-of-sale'),
                'PayPal Point of Sale taxation type',
                $org->taxationType()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Point of Sale customer mode', 'paypal-point-of-sale'),
                'PayPal Point of Sale customer mode',
                $org->customerType()
            );
        } catch (Exception $exc) {
            $container->get('paypal-pos.logger')
                ->warning(sprintf('Failed to load PayPal Point of Sale account info: %1$s', $exc->getMessage()));
        }

        return $items;
    },
];
