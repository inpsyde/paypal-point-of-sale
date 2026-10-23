<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
/**
 * Decides which purchases are synced, by the payment type their order is labelled with.
 * Refunds follow the same rule, so a skipped sale's refund is skipped too.
 */
class PurchaseFilter
{
    private PaymentLabels $paymentLabels;
    /**
     * @var string[]
     */
    private array $paymentTypes;
    /**
     * @param string[] $paymentTypes Zettle payment types to sync; empty syncs all.
     */
    public function __construct(PaymentLabels $paymentLabels, array $paymentTypes)
    {
        $this->paymentLabels = $paymentLabels;
        $this->paymentTypes = $paymentTypes;
    }
    public function allows(Purchase $purchase): bool
    {
        return $this->paymentTypes === [] || in_array($this->paymentLabels->primaryType($purchase->payments()), $this->paymentTypes, \true);
    }
}
