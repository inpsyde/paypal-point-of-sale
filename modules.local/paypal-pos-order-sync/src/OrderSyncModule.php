<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Order\OrderScreenPaymentMethod;
use Syde\PayPal\PointOfSale\OrderSync\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;

class OrderSyncModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }

    public function run(ContainerInterface $container): bool
    {
        $sideEffects = $container->get('paypal-pos.order-sync.order-side-effects');
        assert($sideEffects instanceof OrderSideEffects);
        $sideEffects->suppressEmails();
        $sideEffects->preventStockChanges();
        add_action('current_screen', [OrderScreenPaymentMethod::class, 'register']);

        $this->registerRecovery($container);
        add_action('paypal-pos.onboarding.sync-params.after', [Backfill\SetupImportChoice::class, 'render']);

        $this->registerBackfillTools($container);

        $settingsFilter = $container->get('paypal-pos.order-sync.settings-filter');
        assert($settingsFilter instanceof OrderSyncSettingsFilter);
        add_filter(
            'woocommerce_settings_api_sanitized_fields_' . $container->get('paypal-pos.settings.wc-integration.id'),
            [$settingsFilter, 'filterSettingsValues']
        );

        add_filter('wc_order_attribution_origin_label', [self::class, 'filterOriginLabel'], 10, 3);

        return true;
    }

    /**
     * Show POS orders' Origin as "PayPal POS" instead of "Source: PayPal POS".
     */
    public static function filterOriginLabel(string $label, string $sourceType, string $source): string
    {
        if ($sourceType === PurchaseOrderFactory::ORIGIN_SOURCE_TYPE && $source === PurchaseOrderFactory::ORIGIN_SOURCE) {
            return '';
        }

        return $label;
    }

    private function registerRecovery(ContainerInterface $container): void
    {
        $backfill = static function () use ($container): Backfill\PurchaseBackfill {
            $backfill = $container->get('paypal-pos.order-sync.backfill');
            assert($backfill instanceof Backfill\PurchaseBackfill);

            return $backfill;
        };

        // Import what was sold while the plugin was inactive.
        add_action(
            'paypal-point-of-sale.deactivate',
            static function () use ($backfill) {
                $backfill()->rememberDeactivation();
            }
        );
        add_action(
            'paypal-point-of-sale.activate',
            static function () use ($backfill) {
                $backfill()->recoverAfterReactivation();
            }
        );
        // Imports past sales once, if the merchant kept that onboarding choice on.
        add_action(
            'paypal-pos.onboarding.completed',
            static function () use ($backfill) {
                $backfill()->startSetupImport();
            }
        );
    }

    private function registerBackfillTools(ContainerInterface $container): void
    {
        add_filter(
            'woocommerce_debug_tools',
            static function (array $tools) use ($container): array {
                $backfillTools = $container->get('paypal-pos.order-sync.backfill-tools');
                assert($backfillTools instanceof Backfill\BackfillTools);

                return $backfillTools->addTools($tools);
            }
        );
    }
}
