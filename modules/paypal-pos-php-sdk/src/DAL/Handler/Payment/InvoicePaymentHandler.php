<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\InvoicePaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\InvoicePaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class InvoicePaymentHandler extends AbstractPaymentHandler
{
    private InvoicePaymentBuilder $invoicePaymentBuilder;
    /**
     * InvoicePaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param InvoicePaymentBuilderInterface $invoicePaymentBuilder
     */
    public function __construct(string $validPaymentType, InvoicePaymentBuilderInterface $invoicePaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->invoicePaymentBuilder = $invoicePaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->invoicePaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->invoicePaymentBuilder->buildFromArray($data);
    }
}
