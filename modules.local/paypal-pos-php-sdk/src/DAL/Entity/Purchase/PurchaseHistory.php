<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase;

final class PurchaseHistory
{
    /**
     * @var string
     */
    private $firstPurchaseHash;

    /**
     * @var string
     */
    private $lastPurchaseHash;

    /**
     * @var PurchaseCollection
     */
    private $purchaseCollection;

    /**
     * PurchaseHistory constructor.
     *
     * @param string $firstPurchaseHash
     * @param string $lastPurchaseHash
     * @param PurchaseCollection $purchaseCollection
     */
    public function __construct(
        string $firstPurchaseHash,
        string $lastPurchaseHash,
        PurchaseCollection $purchaseCollection
    ) {

        $this->firstPurchaseHash = $firstPurchaseHash;
        $this->lastPurchaseHash = $lastPurchaseHash;
        $this->purchaseCollection = $purchaseCollection;
    }

    /**
     * @return string
     */
    public function firstPurchaseHash(): string
    {
        return $this->firstPurchaseHash;
    }

    /**
     * @return string
     */
    public function lastPurchaseHash(): string
    {
        return $this->lastPurchaseHash;
    }

    /**
     * @param string $uuid
     * @return Purchase
     */
    public function purchase(string $uuid): Purchase
    {
        return $this->purchaseCollection->get($uuid);
    }

    /**
     * @return PurchaseCollection
     */
    public function purchases(): PurchaseCollection
    {
        return $this->purchaseCollection;
    }

    /**
     * @param Purchase $purchase
     */
    public function addPurchase(Purchase $purchase): void
    {
        $this->purchaseCollection->add($purchase);
    }

    /**
     * @param Purchase $purchase
     */
    public function removePurchase(Purchase $purchase): void
    {
        $this->purchaseCollection->remove($purchase);
    }
}
