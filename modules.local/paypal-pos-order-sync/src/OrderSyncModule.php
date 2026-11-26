<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

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
        $this->registerRecovery($container);
        add_action('paypal-pos.onboarding.sync-params.after', [Backfill\SetupImportChoice::class, 'render']);

        $this->registerBackfillTools($container);
        $this->registerPastSalesNotice($container);

        $settingsFilter = $container->get('paypal-pos.order-sync.settings-filter');
        assert($settingsFilter instanceof OrderSyncSettingsFilter);
        add_filter(
            'woocommerce_settings_api_sanitized_fields_' . $container->get('paypal-pos.settings.wc-integration.id'),
            [$settingsFilter, 'filterSettingsValues']
        );

        return true;
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
            static function () use ($backfill, $container) {
                $backfill()->startSetupImport();
                // The merchant got the import choice, so the notice isn't needed.
                self::pastSalesNotice($container)->dismiss();
            }
        );
    }

    private function registerPastSalesNotice(ContainerInterface $container): void
    {
        add_action(
            'admin_notices',
            static function () use ($container) {
                self::pastSalesNotice($container)->render();
            }
        );
        add_action(
            'woocommerce_hide_' . Backfill\PastSalesNotice::WOOCOMMERCE_NOTICE_NAME . '_notice',
            static function () use ($container) {
                self::pastSalesNotice($container)->dismiss();
            }
        );
    }

    private static function pastSalesNotice(ContainerInterface $container): Backfill\PastSalesNotice
    {
        $notice = $container->get('paypal-pos.order-sync.past-sales-notice');
        assert($notice instanceof Backfill\PastSalesNotice);

        return $notice;
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
