<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use WC_Order;
use WC_Order_Refund;
use WP_Error;

/**
 * Records Zettle refund purchases as WC refunds on the original order, or as a flagged
 * orphan order when the original was not synced yet. Never touches stock.
 */
class PurchaseRefundFactory
{
    private const AMOUNT_TOLERANCE = 0.001;
    private const ARG_REFUND_PURCHASE_UUID = 'paypal_pos_refund_purchase_uuid';
    private const ARG_REFUND_PAYMENTS = 'paypal_pos_refund_payments';

    private OrderRepository $orders;

    private OrderDefaults $defaults;

    private OrderTaxes $taxes;

    private OrderSideEffects $sideEffects;

    private PaymentLabels $paymentLabels;

    private LoggerInterface $logger;

    public function __construct(
        OrderRepository $orders,
        OrderDefaults $defaults,
        OrderTaxes $taxes,
        OrderSideEffects $sideEffects,
        PaymentLabels $paymentLabels,
        LoggerInterface $logger
    ) {

        $this->orders = $orders;
        $this->defaults = $defaults;
        $this->taxes = $taxes;
        $this->sideEffects = $sideEffects;
        $this->paymentLabels = $paymentLabels;
        $this->logger = $logger;
    }

    public function createRefund(Purchase $refund): void
    {
        // Recorded as an orphan before its sale synced; applying it again would count it twice.
        if ($this->orders->findIdByRefundPurchaseUuid($refund->uuid()) !== null) {
            return;
        }

        $originalUuid = (string) $refund->refundsPurchaseUuid();
        $orderId = $this->orders->findIdByPurchaseUuid($originalUuid);
        if ($orderId === null) {
            $this->createOrphanRefund($refund, $originalUuid);

            return;
        }

        $order = wc_get_order($orderId);
        if (!$order instanceof WC_Order) {
            throw new RuntimeException(sprintf('Order %d could not be loaded for refund.', (int) $orderId));
        }

        if ($this->alreadyRefunded($order, $refund->uuid())) {
            return;
        }

        $amount = abs(MinorUnits::toMajor($refund->amount(), $refund->currency()));
        $remaining = (float) $order->get_remaining_refund_amount();
        // E.g. already refunded by hand in WC; wc_create_refund would fail on every retry.
        if ($amount - $remaining > self::AMOUNT_TOLERANCE) {
            $this->skipUnappliableRefund($order, $refund, $amount, $remaining);

            return;
        }

        $this->recordRefund($order, $refund, $originalUuid, $amount);
    }

    /**
     * Notes the link between a just-synced sale and refunds recorded as orphans before it.
     */
    public function linkOrphanRefunds(string $purchaseUuid, int $orderId): void
    {
        $orphanIds = $this->orders->findOrphanIdsForPurchaseUuid($purchaseUuid);
        $sale = $orphanIds !== [] ? wc_get_order($orderId) : null;
        if (!$sale instanceof WC_Order) {
            return;
        }

        foreach ($orphanIds as $orphanId) {
            $orphan = wc_get_order($orphanId);
            if (!$orphan instanceof WC_Order) {
                continue;
            }

            $sale->add_order_note(sprintf(
                /* translators: %d: ID of the orphan refund order */
                __(
                    'A PayPal POS refund of this sale arrived before the sale synced and is recorded in orphan refund order #%d.',
                    'paypal-point-of-sale'
                ),
                $orphanId
            ));
            $orphan->add_order_note(sprintf(
                /* translators: %d: ID of the original sale's order */
                __('The original sale has since synced as order #%d.', 'paypal-point-of-sale'),
                $orderId
            ));
        }
    }

    private function recordRefund(WC_Order $order, Purchase $refund, string $originalUuid, float $amount): void
    {
        // Meta for lookups; the reason already carries the UUID from the refund's first save.
        $tag = static function (WC_Order_Refund $record, array $args): void {
            if (!isset($args[self::ARG_REFUND_PURCHASE_UUID])) {
                return;
            }
            $record->update_meta_data(OrderMeta::REFUND_PURCHASE_UUID, $args[self::ARG_REFUND_PURCHASE_UUID]);
            $record->update_meta_data(OrderMeta::REFUND_PAYMENTS, $args[self::ARG_REFUND_PAYMENTS]);
        };
        add_action('woocommerce_create_refund', $tag, 10, 2);

        try {
            $result = wc_create_refund([
                'order_id' => $order->get_id(),
                'amount' => (string) $amount,
                'reason' => $this->reason($refund, $originalUuid),
                'line_items' => RefundLines::forRefund($order, $refund->products(), $refund->currency()),
                'refund_payment' => false,
                'restock_items' => false,
                self::ARG_REFUND_PURCHASE_UUID => $refund->uuid(),
                self::ARG_REFUND_PAYMENTS => (string) wp_json_encode(array_values($refund->payments())),
            ]);
        } finally {
            remove_action('woocommerce_create_refund', $tag, 10);
        }

        if ($result instanceof WP_Error) {
            throw new RuntimeException(esc_html($result->get_error_message()));
        }
    }

    private function alreadyRefunded(WC_Order $order, string $refundPurchaseUuid): bool
    {
        foreach ($order->get_refunds() as $existing) {
            // The reason is saved with the refund itself, so it survives a crash before tagging.
            if (
                $existing->get_meta(OrderMeta::REFUND_PURCHASE_UUID) === $refundPurchaseUuid
                || str_contains((string) $existing->get_reason(), $refundPurchaseUuid)
            ) {
                return true;
            }
        }

        return false;
    }

    private function skipUnappliableRefund(WC_Order $order, Purchase $refund, float $amount, float $remaining): void
    {
        $message = sprintf(
            /* translators: 1: refund purchase UUID, 2: refund amount, 3: amount left to refund */
            __(
                'PayPal POS refund %1$s of %2$s could not be recorded: only %3$s is left to refund on this order.',
                'paypal-point-of-sale'
            ),
            $refund->uuid(),
            wc_format_decimal($amount),
            wc_format_decimal($remaining)
        );

        $this->logger->warning(sprintf('Order %d: %s', $order->get_id(), $message));
        $order->add_order_note($message);
    }

    private function createOrphanRefund(Purchase $refund, string $originalUuid): void
    {
        $this->logger->warning(sprintf(
            'Refund %s references purchase %s which has no synced order; creating an orphan refund order.',
            $refund->uuid(),
            $originalUuid !== '' ? $originalUuid : '(unknown)'
        ));

        $this->sideEffects->withoutStockReduction(function () use ($refund, $originalUuid): void {
            $order = new WC_Order();
            $currency = $refund->currency();

            $taxTotals = [];
            $linesMinor = 0;
            foreach ($refund->products() as $line) {
                $linesMinor += $this->addOrphanLine($order, (array) $line, $currency, $refund->country(), $taxTotals);
            }
            $this->taxes->alignToPurchaseVat($order, $taxTotals, (int) round($refund->vatAmount()), $currency);
            $taxMinor = $this->taxes->addTaxItems($order, $taxTotals, $currency);
            OrderFees::addUnlistedCharges($order, $refund, (int) abs($refund->amount()) - $linesMinor, -1);

            $this->defaults->apply($order, $refund);
            $order->update_meta_data(OrderMeta::REFUND_PURCHASE_UUID, $refund->uuid());
            $order->update_meta_data(OrderMeta::ORPHAN_REFUND, 'yes');
            $order->update_meta_data(OrderMeta::ORPHAN_REFUNDS_PURCHASE_UUID, $originalUuid);
            $order->set_cart_tax((string) MinorUnits::toMajor($taxMinor, $currency));
            $order->set_total((string) MinorUnits::toMajor($refund->amount(), $currency));
            $order->set_status('completed');
            $order->set_date_paid($refund->timestamp()->getTimestamp());
            $order->set_date_completed($refund->timestamp()->getTimestamp());
            $order->save();

            $order->add_order_note(sprintf(
                /* translators: %s: refund description */
                __('%s (original order not found)', 'paypal-point-of-sale'),
                $this->reason($refund, $originalUuid)
            ));
        });
    }

    /**
     * @param array<string, mixed> $line
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     *
     * @return int The line's refunded amount with tax, in minor units.
     */
    private function addOrphanLine(
        WC_Order $order,
        array $line,
        string $currency,
        string $country,
        array &$taxTotals
    ): int {

        $amounts = LineAmounts::fromLine($line);
        $rateId = $this->taxes->addLineTax($taxTotals, $amounts, $country, -1);

        $name = sprintf(
            /* translators: 1: refunded item name, 2: refunded quantity */
            __('Refund: %1$s × %2$s', 'paypal-point-of-sale'),
            (string) ($line[PurchaseLine::NAME] ?? '') ?: PurchaseOrderFactory::defaultLineName(),
            (string) $amounts->quantity()
        );
        $fee = OrderFees::create($name, -$amounts->netMinor(), $currency);
        if ($rateId > 0) {
            $fee->set_taxes(['total' => [$rateId => (string) -MinorUnits::toMajor($amounts->taxMinor(), $currency)]]);
        }
        $order->add_item($fee);

        return $amounts->netMinor() + $amounts->taxMinor();
    }

    private function reason(Purchase $refund, string $originalUuid): string
    {
        $tender = $this->paymentLabels->title($this->paymentLabels->primaryType($refund->payments()));

        return sprintf(
            /* translators: 1: refund purchase UUID, 2: payment method title, 3: original purchase UUID */
            __('Refund %1$s via %2$s of purchase %3$s', 'paypal-point-of-sale'),
            $refund->uuid(),
            $tender,
            $originalUuid !== '' ? $originalUuid : __('(unknown)', 'paypal-point-of-sale')
        );
    }
}
