<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use WC_Payment_Gateway;

/**
 * Display-only stand-in for a POS order's payment method. WC prints the raw payment method
 * id on the order screen unless a gateway with that id is registered; this one is disabled,
 * so it can't be used at checkout.
 */
class PosPaymentMethod extends WC_Payment_Gateway
{
    public function __construct(string $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
        $this->method_title = $title;
        $this->enabled = 'no';
        $this->has_fields = false;
    }
}
