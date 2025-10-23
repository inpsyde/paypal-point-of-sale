<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use DateTime;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
final class InvoicePayment extends AbstractPaymentMethod
{
    /**
     * @var string
     */
    private $orderUuid;
    /**
     * @var string
     */
    private $invoiceNumber;
    /**
     * @var DateTime
     */
    private $dueDate;
    /**
     * InvoicePayment constructor.
     *
     * @param string $uuid
     * @param float $amount
     * @param string $orderUuid
     * @param string $invoiceNumber
     * @param DateTime $dueDate
     */
    public function __construct(string $uuid, float $amount, string $orderUuid, string $invoiceNumber, DateTime $dueDate)
    {
        parent::__construct($uuid, $amount, PaymentType::invoicePayment());
        $this->orderUuid = $orderUuid;
        $this->invoiceNumber = $invoiceNumber;
        $this->dueDate = $dueDate;
    }
    /**
     * @return string
     */
    public function orderUuid(): string
    {
        return $this->orderUuid;
    }
    /**
     * @return string
     */
    public function invoiceNumber(): string
    {
        return $this->invoiceNumber;
    }
    /**
     * @return DateTime
     */
    public function dueDate(): DateTime
    {
        return $this->dueDate;
    }
}
