<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance;

use DateTime;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\Type\TransactionType;
final class AccountTransaction
{
    private DateTime $timestamp;
    private int $amount;
    private TransactionType $originatorTransactionType;
    private string $originatingTransactionUuid;
    /**
     * AccountTransaction constructor.
     *
     * @param DateTime $timestamp
     * @param int $amount
     * @param TransactionType $originatorTransactionType
     * @param string $originatingTransactionUuid
     */
    public function __construct(DateTime $timestamp, int $amount, TransactionType $originatorTransactionType, string $originatingTransactionUuid)
    {
        $this->timestamp = $timestamp;
        $this->amount = $amount;
        $this->originatorTransactionType = $originatorTransactionType;
        $this->originatingTransactionUuid = $originatingTransactionUuid;
    }
    /**
     * @return DateTime
     */
    public function timestamp(): DateTime
    {
        return $this->timestamp;
    }
    /**
     * @return int
     */
    public function amount(): int
    {
        return $this->amount;
    }
    /**
     * @return TransactionType
     */
    public function originatorTransactionType(): TransactionType
    {
        return $this->originatorTransactionType;
    }
    /**
     * @return string
     */
    public function originatingTransactionUuid(): string
    {
        return $this->originatingTransactionUuid;
    }
}
