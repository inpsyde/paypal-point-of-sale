<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

final class VariantCollection
{
    /**
     * @var VariantInterface[]
     */
    private array $collection = [];
    public function __construct(VariantInterface ...$variants)
    {
        foreach ($variants as $variant) {
            $this->add($variant);
        }
    }
    /**
     * @param VariantInterface $variant
     *
     * @return VariantCollection
     */
    public function add(VariantInterface $variant): self
    {
        $this->collection[spl_object_hash($variant)] = $variant;
        return $this;
    }
    /**
     * @param VariantInterface $variant
     *
     * @return VariantCollection
     */
    public function remove(VariantInterface $variant): self
    {
        unset($this->collection[spl_object_hash($variant)]);
        return $this;
    }
    public function get(string $uuid): ?VariantInterface
    {
        foreach ($this->collection as $item) {
            if ((string) $item->uuid() === $uuid) {
                return $item;
            }
        }
        return null;
    }
    /**
     * @return Variant[]
     */
    public function all(): array
    {
        return $this->collection;
    }
    /**
     * @return VariantCollection
     */
    public function reset(): self
    {
        $this->collection = [];
        return $this;
    }
}
