<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase;

use DateTime;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates\Coordinates;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Type\SourceType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\User;

final class Purchase
{
    private string $uuid;

    private string $uuid1;

    private DateTime $timestamp;

    private ?Coordinates $coordinates = null;

    private string $country;

    private string $currency;

    private User $user;

    private int $organizationId;

    private int $purchaseNumber;

    private float $amount;

    private float $vatAmount;

    private array $products;

    private array $payments;

    private array $vatAmounts;

    private bool $receiptCopyAllowed;

    private ?bool $published = null;

    private bool $refund;

    private bool $refunded;

    private ?SourceType $sourceType = null;

    /**
     * Purchase constructor.
     *
     * @TODO: Maybe use Collections instead of arrays
     *
     * @param string $uuid
     * @param string $uuid1
     * @param DateTime $timestamp
     * @param string $country
     * @param string $currency
     * @param User $user
     * @param int $organizationId
     * @param int $purchaseNumber
     * @param float $amount
     * @param float $vatAmount
     * @param array $products
     * @param array $payments
     * @param array $vatAmounts
     * @param bool $receiptCopyAllowed
     * @param bool $refund
     * @param bool $refunded
     * @param Coordinates|null $coordinates
     * @param SourceType|null $sourceType
     * @param bool|null $published
     */
    public function __construct(
        string $uuid,
        string $uuid1,
        DateTime $timestamp,
        string $country,
        string $currency,
        User $user,
        int $organizationId,
        int $purchaseNumber,
        float $amount,
        float $vatAmount,
        array $products,
        array $payments,
        array $vatAmounts,
        bool $receiptCopyAllowed,
        bool $refund,
        bool $refunded,
        ?Coordinates $coordinates = null,
        ?SourceType $sourceType = null,
        ?bool $published = null
    ) {

        $this->uuid = $uuid;
        $this->uuid1 = $uuid1;
        $this->timestamp = $timestamp;
        $this->country = $country;
        $this->currency = $currency;
        $this->user = $user;
        $this->organizationId = $organizationId;
        $this->purchaseNumber = $purchaseNumber;
        $this->amount = $amount;
        $this->vatAmount = $vatAmount;
        $this->products = $products;
        $this->payments = $payments;
        $this->vatAmounts = $vatAmounts;
        $this->receiptCopyAllowed = $receiptCopyAllowed;
        $this->refund = $refund;
        $this->refunded = $refunded;
        $this->coordinates = $coordinates;
        $this->sourceType = $sourceType;
        $this->published = $published;
    }

    /**
     * @return string
     */
    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function uuid1(): string
    {
        return $this->uuid1;
    }

    /**
     * @return DateTime
     */
    public function timestamp(): DateTime
    {
        return $this->timestamp;
    }

    /**
     * @return Coordinates|null
     */
    public function coordinates(): ?Coordinates
    {
        return $this->coordinates;
    }

    /**
     * @return string
     */
    public function country(): string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * @return User
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function organizationId(): int
    {
        return $this->organizationId;
    }

    /**
     * @return int
     */
    public function purchaseNumber(): int
    {
        return $this->purchaseNumber;
    }

    /**
     * @return float
     */
    public function amount(): float
    {
        return $this->amount;
    }

    /**
     * @return float
     */
    public function vatAmount(): float
    {
        return $this->vatAmount;
    }

    /**
     * @return array
     */
    public function products(): array
    {
        return $this->products;
    }

    /**
     * @return array
     */
    public function payments(): array
    {
        return $this->payments;
    }

    /**
     * @return array
     */
    public function vatAmounts(): array
    {
        return $this->vatAmounts;
    }

    /**
     * @return bool
     */
    public function isReceiptCopyAllowed(): bool
    {
        return $this->receiptCopyAllowed;
    }

    /**
     * @return bool|null
     */
    public function isPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * @return bool
     */
    public function isRefund(): bool
    {
        return $this->refund;
    }

    /**
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->refunded;
    }

    /**
     * @return SourceType|null
     */
    public function sourceType(): ?SourceType
    {
        return $this->sourceType;
    }
}
