<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Order\OrderScreenPaymentMethod;
use Syde\PayPal\PointOfSale\OrderSync\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;

/**
 * Handles the POS orders that order sync created. Unlike OrderSyncModule, it isn't behind the
 * order-sync flag: the orders stay when the flag is turned off, and editing one must still not
 * reduce stock again.
 */
class PosOrdersModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return [
            'paypal-pos.order-sync.order-side-effects' => static function (): OrderSideEffects {
                return new OrderSideEffects();
            },
        ];
    }

    public function extensions(): array
    {
        return [
            // Onboarding again re-imports the history; already synced sales and refunds are skipped.
            'paypal-pos.onboarding.resettable.options' => static function (array $previous): array {
                return array_merge($previous, [PurchaseBackfill::STATUS_OPTION, PurchaseBackfill::WEBHOOK_GAP_OPTION]);
            },
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        $sideEffects = $container->get('paypal-pos.order-sync.order-side-effects');
        assert($sideEffects instanceof OrderSideEffects);
        $sideEffects->suppressEmails();
        $sideEffects->preventStockChanges();
        add_action('current_screen', [OrderScreenPaymentMethod::class, 'register']);
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
}
