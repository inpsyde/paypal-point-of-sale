<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

class LocationBalanceCollection
{
    /**
     * @var LocationBalance[]
     */
    private array $collection = [];

    /**
     * LocationBalanceCollection constructor.
     *
     * @param array|null $locationBalances
     */
    public function __construct(?array $locationBalances = [])
    {
        foreach ($locationBalances as $locationBalance) {
            if ($locationBalance instanceof LocationBalance) {
                $this->add($locationBalance);
            }
        }
    }

    /**
     * @param LocationBalance $locationBalance
     *
     * @return LocationBalanceCollection
     */
    public function add(LocationBalance $locationBalance): self
    {
        $this->collection[$locationBalance->locationUuid()] = $locationBalance;

        return $this;
    }

    /**
     * @param LocationBalance $locationBalance
     *
     * @return LocationBalanceCollection
     */
    public function remove(LocationBalance $locationBalance): self
    {
        unset($this->collection[$locationBalance->locationUuid()]);

        return $this;
    }

    /**
     * @param string $locationUuid
     *
     * @return LocationBalance
     */
    public function get(string $locationUuid): LocationBalance
    {
        return $this->collection[$locationUuid];
    }

    /**
     * @return LocationBalance[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return LocationBalanceCollection
     */
    public function reset(): self
    {
        $this->collection = [];

        return $this;
    }
}
