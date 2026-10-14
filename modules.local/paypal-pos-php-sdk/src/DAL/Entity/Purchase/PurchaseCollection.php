<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase;

final class PurchaseCollection
{
    /**
     * @var Purchase[]
     */
    private array $collection = [];

    /**
     * PurchaseCollection constructor.
     *
     * @param array|null $purchases
     */
    public function __construct(?array $purchases = [])
    {
        foreach ($purchases ?? [] as $purchase) {
            if ($purchase instanceof Purchase) {
                $this->add($purchase);
            }
        }
    }

    /**
     * @param Purchase $purchase
     *
     * @return PurchaseCollection
     */
    public function add(Purchase $purchase): self
    {
        $this->collection[(string) $purchase->uuid()] = $purchase;

        return $this;
    }

    /**
     * @param Purchase $purchase
     *
     * @return PurchaseCollection
     */
    public function remove(Purchase $purchase): self
    {
        unset($this->collection[(string) $purchase->uuid()]);

        return $this;
    }

    /**
     * @param string $uuid
     *
     * @return Purchase
     */
    public function get(string $uuid): Purchase
    {
        return $this->collection[(string) $uuid];
    }

    /**
     * @return Purchase[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return PurchaseCollection
     */
    public function reset(): self
    {
        $this->collection = [];

        return $this;
    }
}
