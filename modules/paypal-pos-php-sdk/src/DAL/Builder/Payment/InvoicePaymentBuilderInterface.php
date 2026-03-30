<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\InvoicePayment;
interface InvoicePaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return InvoicePayment
     */
    public function buildFromArray(array $data): InvoicePayment;
    /**
     * @param InvoicePayment $invoicePayment
     *
     * @return array
     */
    public function createDataArray(InvoicePayment $invoicePayment): array;
}
