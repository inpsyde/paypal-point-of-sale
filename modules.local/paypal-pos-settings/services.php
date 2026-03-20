<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings;

use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingState;
use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\PayPal\PointOfSale\Provider;
use Syde\PayPal\PointOfSale\Settings\Provider\SettingsPageProvider;
use Syde\PayPal\PointOfSale\Settings\WC\SettingsPage;
use Syde\PayPal\PointOfSale\Settings\WC\ZettleIntegration;
use Syde\PayPal\PointOfSale\Settings\WC\ZettleIntegrationHeader;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

return [
    'paypal-pos.settings.url' => static function (C $container): string {
        return admin_url('admin.php?page=wc-settings&tab=zettle');
    },
    'paypal-pos.settings.is-integration-page' => static function (C $container): callable {
        return static function (): bool {
            return filter_input(INPUT_GET, 'tab') === 'zettle';
        };
    },
    'paypal-pos.settings.shop-link' => static function (C $container): array {
        return [
            'title' => esc_html__('Order PayPal Point of Sale Hardware', 'paypal-point-of-sale'),
            'url' => esc_url_raw('https://shop.zettle.com/'),
            'icon' => true,
        ];
    },
    'paypal-pos.settings.account.link.api-key-creation-url' => static function (C $container): string {
        return add_query_arg(
            [
                'name' => 'WooCommerce integration',
                'scopes' => implode(
                    '%20',
                    [
                        'READ:FINANCE',
                        'READ:PURCHASE',
                        'READ:USERINFO',
                        'READ:PRODUCT',
                        'WRITE:PRODUCT',
                    ]
                ),
                'utm_source' => 'local_partnership',
                'utm_medium' => 'ecommerce',
                'utm_campaign' => 'woocommerce',
            ],
            'https://my.zettle.com/apps/api-keys'
        );
    },
    'paypal-pos.settings.account.link.signup' => static function (): string {
        $utm = '?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce';

        $urls = [
            'GB' => 'https://www.zettle.com/gb/integrations/e-commerce/woocommerce' . $utm,
            'FR' => 'https://www.zettle.com/fr/integrations/e-commerce/woocommerce' . $utm,
            'SE' => 'https://www.zettle.com/se/integrationer/e-handel/woocommerce' . $utm,
            'NO' => 'https://www.zettle.com/no/integrasjoner/e-handel/woocommerce' . $utm,
            'FI' => 'https://www.zettle.com/fi/integraatiot/verkkokauppa/woocommerce' . $utm,
            'DK' => 'https://www.zettle.com/dk/integrationer/e-commerce/woocommerce' . $utm,
            'NL' => 'https://www.zettle.com/nl/koppelingen/webshop/woocommerce' . $utm,
            'DE' => 'https://www.zettle.com/de/integrationen/e-commerce/woocommerce' . $utm,
            'ES' => 'https://www.zettle.com/es/integraciones/woocommerce' . $utm,
            'IT' => 'https://www.zettle.com/it/integrazioni/woocommerce' . $utm,
            'BR' => 'https://www.zettle.com/br/integracoes/woocommerce' . $utm,
            'MX' => 'https://www.zettle.com/mx/integraciones/woocommerce' . $utm,
            'US' => 'https://www.paypal.com/us/business/pos' . $utm,
        ];
        $location = wc_get_base_location();
        if (isset($urls[$location['country']])) {
            return $urls[$location['country']];
        }

        return $urls['GB'];
    },
    'paypal-pos.settings.account.link.signup-login' => static function (C $container): callable {
        return static function (bool $loggedIn) use ($container): array {
            $accountLink = [
                'title' => esc_html__('Create account now', 'paypal-point-of-sale'),
                'url' => esc_url_raw($container->get('paypal-pos.settings.account.link.signup')),
                'icon' => true,
            ];

            if ($loggedIn) {
                return $container->get('paypal-pos.settings.account.link.api')($loggedIn);
            }

            return $accountLink;
        };
    },
    'paypal-pos.settings.account.link.api-key-creation' => static function (C $container): array {
        return [
            'title' => esc_html__('Create API key', 'paypal-point-of-sale'),
            'url' => esc_url_raw(
                $container->get('paypal-pos.settings.account.link.api-key-creation-url')
            ),
            'icon' => true,
            'popup' => true,
        ];
    },
    'paypal-pos.settings.account.link.api' => static function (C $container): callable {
        return static function (bool $loggedIn) use ($container): array {
            if ($loggedIn) {
                return [
                    'title' => esc_html__('Manage account', 'paypal-point-of-sale'),
                    'url' => esc_url_raw('https://my.zettle.com/'),
                    'icon' => true,
                ];
            }

            return $container->get('paypal-pos.settings.account.link.api-key-creation');
        };
    },
    'paypal-pos.settings.account.link' => static function (C $container): callable {
        return static function (bool $loggedIn) use ($container): array {
            $stateMachine = $container->get('inpsyde.state-machine');
            assert($stateMachine instanceof StateMachineInterface);

            switch ($stateMachine->currentState()->name()) {
                case OnboardingState::WELCOME:
                    return $container->get('paypal-pos.settings.account.link.signup-login')($loggedIn);
                case OnboardingState::API_CREDENTIALS:
                default:
                    return $container->get('paypal-pos.settings.account.link.api')($loggedIn);
            }
        };
    },
    'paypal-pos.settings.account.disconnect' => static function (C $container): array {
        return [
            'title' => __('Disconnect', 'paypal-point-of-sale'),
            'name' => 'delete',
            'value' => '',
            'class' => 'btn btn-delete-link',
            'icon' => false,
            'dialog' => [
                'id' => $container->get('paypal-pos.settings.account.disconnect.id'),
                'title' => __(
                    'Are you sure you want to disconnect?',
                    'paypal-point-of-sale'
                ),
                'content' => __(
                    '<p>This will remove the connection to PayPal Point of Sale.</p>
                     <p>Already synced products in PayPal Point of Sale will not be deleted, but no longer update.</p>',
                    'paypal-point-of-sale'
                ),
                'buttons' => [
                    [
                        'action' => ButtonAction::BACK,
                        'label' => __('Cancel', 'paypal-point-of-sale'),
                    ],
                    [
                        'action' => ButtonAction::DELETE,
                        'label' => __('Disconnect', 'paypal-point-of-sale'),
                    ],
                ],
            ],
        ];
    },
    'paypal-pos.settings.account.disconnect.id' => static function (C $container): string {
        return 'zettleDisconnectModal';
    },
    'paypal-pos.settings.wc-integration.id' => static function (C $container): string {
        return 'zettle';
    },
    'paypal-pos.settings.wc-integration.title' => static function (C $container): string {
        return __('PayPal Point of Sale', 'paypal-point-of-sale');
    },
    'paypal-pos.settings.wc-integration.link' => static function (C $container): callable {
        return static function (bool $loggedIn): array {
            $linkData = [
                'title' => esc_html__('paypal-pos.com', 'paypal-point-of-sale'),
                'url' => esc_url_raw('https://zettle.com/'),
                'icon' => true,
            ];

            if ($loggedIn) {
                $linkData['url'] = esc_url_raw('https://my.zettle.com/');
            }

            return $linkData;
        };
    },
    'paypal-pos.settings.wc-integration.description' => static function (C $container): string {
        return __('Point-of-sale system', 'paypal-point-of-sale');
    },
    'paypal-pos.settings.wc-integration.header' =>
        static function (C $container): ZettleIntegrationHeader {
            $stateMachine = $container->get('inpsyde.state-machine');
            assert($stateMachine instanceof StateMachineInterface);

            return new ZettleIntegrationHeader(
                $container->get('paypal-pos.settings.account.link'),
                $container->get('paypal-pos.settings.shop-link'),
                $container->get('paypal-pos.settings.wc-integration.link'),
                $container->get('paypal-pos.sdk.dal.provider.organization'),
                $container->get('paypal-pos.settings.account.disconnect'),
                $container->get('paypal-pos.sdk.id-map.product'),
                $container->get('paypal-pos.onboarding.first-import-timestamp'),
                $container->get('paypal-pos.format-timestamp'),
                $container->get('paypal-pos.sync.price-sync-enabled'),
                $container->get('paypal-pos.settings.wc-integration.title'),
                $container->get('paypal-pos.settings.wc-integration.description'),
                $stateMachine->currentState()->name(),
                $container->get('paypal-pos.onboarding.api-auth-check')
            );
        },
    'paypal-pos.settings.wc-integration' => static function (C $container): ZettleIntegration {
        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        return new ZettleIntegration(
            $container->get('paypal-pos.settings.wc-integration.id'),
            $container->get('paypal-pos.settings.wc-integration.header'),
            $stateMachine->currentState()->name(),
            $container->get('paypal-pos.settings.fields'),
            $container->get('paypal-pos.settings.is-integration-page'),
            $container->get('paypal-pos.settings'),
            ...$container->get('paypal-pos.settings.field-renderers')
        );
    },
    'paypal-pos.settings.is-settings-save-request' => static function (C $container): bool {
        return filter_input(INPUT_POST, 'save') &&
            $container->get('paypal-pos.settings.is-integration-page')();
    },
    'paypal-pos.settings.page.factory' => static function (C $container): callable {
        return static function () use ($container): SettingsPage {
            return new SettingsPage(
                $container->get('paypal-pos.settings.wc-integration'),
                $container->get('paypal-pos.settings.wc-integration.id'),
                $container->get('paypal-pos.settings.wc-integration.title'),
                $container->get('paypal-pos.logger.woocommerce'),
                $container->get('paypal-pos.throw-unhandled-errors')
            );
        };
    },
    'paypal-pos.settings.fields.registry' => static function (): array {
        return [];
    },
    'paypal-pos.settings.fields' => static function (C $container): array {
        /**
         * We're just proxying another entry here. This will make it possible
         * to use separate extensions for registration and filtering of
         * settings fields, which is used by the onboarding module
         */
        return $container->get('paypal-pos.settings.fields.registry');
    },
    'paypal-pos.settings.field-renderers' => static function (): array {
        return [];
    },
    'paypal-pos.settings.provider.settings-page' => static function (C $container): Provider {
        return new SettingsPageProvider(
            $container->get('paypal-pos.settings.page.factory')
        );
    },
    'paypal-pos.settings.provider' => static function (C $container): array {
        return [
            $container->get('paypal-pos.settings.provider.settings-page'),
        ];
    },
];
