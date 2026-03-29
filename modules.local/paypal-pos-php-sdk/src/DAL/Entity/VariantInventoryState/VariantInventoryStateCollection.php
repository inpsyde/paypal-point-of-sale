<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantInventoryState;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;

class VariantInventoryStateCollection
{
    /**
     * @var VariantInventoryState[]
     */
    private array $collection = [];

    /**
     * VariantChangeHistoryCollection constructor.
     *
     * @param array|null $inventoryStates
     */
    public function __construct(?array $inventoryStates = [])
    {
        foreach ($inventoryStates as $inventoryState) {
            if ($inventoryState instanceof VariantInventoryState) {
                $this->add($inventoryState);
            }
        }
    }

    /**
     * @param VariantInventoryState $variantInventoryState
     *
     * @return VariantInventoryState
     */
    public function add(VariantInventoryState $variantInventoryState): self
    {
        $this->collection[(string) $variantInventoryState->variantUuid()] = $variantInventoryState;

        return $this;
    }

    /**
     * @param VariantInventoryState $variantInventoryState
     *
     * @return VariantInventoryState
     */
    public function remove(VariantInventoryState $variantInventoryState): self
    {
        unset($this->collection[(string) $variantInventoryState->variantUuid()]);

        return $this;
    }

    /**
     * @param string $uuid
     *
     * @return VariantInventoryState
     * @throws IdNotFoundException
     */
    public function get(string $uuid): VariantInventoryState
    {
        if (!array_key_exists($uuid, $this->collection)) {
            throw new IdNotFoundException("Variant-UUID {$uuid} not found in Inventory");
        }

        return $this->collection[(string) $uuid];
    }

    /**
     * @return VariantInventoryState[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return VariantInventoryState
     */
    public function reset(): self
    {
        $this->collection = [];

        return $this;
    }
}
