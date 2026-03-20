<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount;

final class DiscountCollection
{
    /**
     * @var Discount[]
     */
    private $collection = [];
    /**
     * DiscountCollection constructor.
     *
     * @param array|null $discounts
     */
    public function __construct(?array $discounts = [])
    {
        foreach ($discounts as $discount) {
            $this->add($discount);
        }
    }
    /**
     * @param Discount $discount
     *
     * @return DiscountCollection
     */
    public function add(Discount $discount): self
    {
        $this->collection[$discount->uuid()] = $discount;
        return $this;
    }
    /**
     * @param Discount $discount
     *
     * @return DiscountCollection
     */
    public function remove(Discount $discount): self
    {
        unset($this->collection[$discount->uuid()]);
        return $this;
    }
    /**
     * @param string $uuid
     *
     * @return Discount
     */
    public function get(string $uuid): Discount
    {
        return $this->collection[$uuid];
    }
    /**
     * @return Discount[]
     */
    public function all(): array
    {
        return $this->collection;
    }
    /**
     * @return DiscountCollection
     */
    public function reset(): self
    {
        $this->collection = [];
        return $this;
    }
}
