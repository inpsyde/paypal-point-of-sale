<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption;

class VariantOptionCollection
{
    /**
     * @var VariantOption[]
     */
    private array $collection = [];
    /**
     * VariantOptionCollection constructor.
     *
     * @param VariantOption[] $variantOptions
     */
    public function __construct(VariantOption ...$variantOptions)
    {
        foreach ($variantOptions as $variantOption) {
            $this->add($variantOption);
        }
    }
    /**
     * @param VariantOption $variantOption
     *
     * @return VariantOptionCollection
     */
    public function add(VariantOption $variantOption): self
    {
        $this->collection[] = $variantOption;
        return $this;
    }
    /**
     * @param VariantOption $variantOption
     *
     * @return VariantOptionCollection
     */
    public function remove(VariantOption $variantOption): self
    {
        foreach ($this->collection as $id => $item) {
            if ($item->name() === $variantOption->name()) {
                unset($this->collection[$id]);
            }
        }
        return $this;
    }
    /**
     * @param VariantOption $variantOption
     *
     * @return VariantOption|null
     */
    public function get(VariantOption $variantOption): ?VariantOption
    {
        foreach ($this->collection as $item) {
            if ($item->name() === $variantOption->name()) {
                return $item;
            }
        }
        return null;
    }
    /**
     * @param string $key
     *
     * @return VariantOption|null
     */
    public function byName(string $key): ?VariantOption
    {
        foreach ($this->collection as $item) {
            if ($item->name() === $key) {
                return $item;
            }
        }
        return null;
    }
    public function set(string $key, string $value)
    {
        $existing = $this->byName($key);
        if (!$existing) {
            $existing = new VariantOption($key, $value);
            $this->add($existing);
        }
        $existing->setValue($value);
    }
    /**
     * @return VariantOption[]
     */
    public function all(): ?array
    {
        return $this->collection;
    }
    /**
     * @return VariantOptionCollection
     */
    public function reset(): self
    {
        $this->collection = [];
        return $this;
    }
}
