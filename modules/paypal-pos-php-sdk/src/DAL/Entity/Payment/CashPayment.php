<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
final class CashPayment extends AbstractPaymentMethod
{
    /**
     * @var float
     */
    private $handedAmount;
    /**
     * CashPayment constructor.
     *
     * @param string $uuid
     * @param float $amount
     * @param float $handedAmount
     */
    public function __construct(string $uuid, float $amount, float $handedAmount)
    {
        parent::__construct($uuid, $amount, PaymentType::cashPayment());
        $this->handedAmount = $handedAmount;
    }
    /**
     * @return float
     */
    public function handedAmount(): float
    {
        return $this->handedAmount;
    }
    /**
     * @return float
     */
    public function changeAmount(): float
    {
        return $this->handedAmount() - $this->amount();
    }
}
