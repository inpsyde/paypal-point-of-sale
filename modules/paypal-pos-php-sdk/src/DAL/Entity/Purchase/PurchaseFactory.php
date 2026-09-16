<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase;

use DateTime;
use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates\Coordinates;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Type\SourceType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\User;
use Syde\Vendor\Zettle\Symfony\Component\Uid\Uuid;
final class PurchaseFactory
{
    /**
     * @param string $uuid
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
     *
     * @return Purchase
     *
     * @throws Exception
     */
    public function create(string $uuid, DateTime $timestamp, string $country, string $currency, User $user, int $organizationId, int $purchaseNumber, float $amount, float $vatAmount, array $products, array $payments, array $vatAmounts, bool $receiptCopyAllowed, bool $refund, bool $refunded, ?Coordinates $coordinates = null, ?SourceType $sourceType = null, ?bool $published = null): Purchase
    {
        return new Purchase($uuid, (string) Uuid::v1(), $timestamp, $country, $currency, $user, $organizationId, $purchaseNumber, $amount, $vatAmount, $products, $payments, $vatAmounts, $receiptCopyAllowed, $refund, $refunded, $coordinates, $sourceType, $published);
    }
}
