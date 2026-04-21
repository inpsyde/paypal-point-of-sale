<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Library;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount\DiscountCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;

final class Library
{
    private string $untilEventLogUuid;

    private string $fromEventLogUuid;

    private ProductCollection $products;

    private DiscountCollection $discounts;

    private ?ProductCollection $deletedProducts = null;

    private ?DiscountCollection $deletedDiscounts = null;

    /**
     * Library constructor.
     *
     * @param string $untilEventLogUuid
     * @param string $fromEventLogUuid
     * @param ProductCollection $products
     * @param DiscountCollection $discounts
     * @param ProductCollection|null $deletedProducts
     * @param DiscountCollection|null $deletedDiscounts
     */
    public function __construct(
        string $untilEventLogUuid,
        string $fromEventLogUuid,
        ProductCollection $products,
        DiscountCollection $discounts,
        ?ProductCollection $deletedProducts = null,
        ?DiscountCollection $deletedDiscounts = null
    ) {

        $this->untilEventLogUuid = $untilEventLogUuid;
        $this->products = $products;
        $this->discounts = $discounts;
        $this->deletedProducts = $deletedProducts;
        $this->deletedDiscounts = $deletedDiscounts;
        $this->fromEventLogUuid = $fromEventLogUuid;
    }

    /**
     * @return string
     */
    public function untilEventLogUuid(): string
    {
        return $this->untilEventLogUuid;
    }

    /**
     * @return string|null
     */
    public function fromEventLogUuid(): ?string
    {
        return $this->fromEventLogUuid;
    }

    /**
     * @return ProductCollection
     */
    public function products(): ProductCollection
    {
        return $this->products;
    }

    /**
     * @return DiscountCollection
     */
    public function discounts(): DiscountCollection
    {
        return $this->discounts;
    }

    /**
     * @return ProductCollection|null
     */
    public function deletedProducts(): ?ProductCollection
    {
        return $this->deletedProducts;
    }

    /**
     * @return DiscountCollection|null
     */
    public function deletedDiscounts(): ?DiscountCollection
    {
        return $this->deletedDiscounts;
    }
}
