<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Balance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\LocationBalanceCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\LocationBalanceCollectionFactory;

class LocationBalanceCollectionBuilder implements LocationBalanceCollectionBuilderInterface
{
    private LocationBalanceCollectionFactory $locationBalanceCollectionFactory;

    private LocationBalanceBuilderInterface $locationBalanceBuilder;

    /**
     * LocationBalanceCollectionBuilder constructor.
     *
     * @param LocationBalanceCollectionFactory $locationBalanceCollectionFactory
     * @param LocationBalanceBuilderInterface $locationBalanceBuilder
     */
    public function __construct(
        LocationBalanceCollectionFactory $locationBalanceCollectionFactory,
        LocationBalanceBuilderInterface $locationBalanceBuilder
    ) {

        $this->locationBalanceCollectionFactory = $locationBalanceCollectionFactory;
        $this->locationBalanceBuilder = $locationBalanceBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): LocationBalanceCollection
    {
        $locationBalanceCollection = $this->locationBalanceCollectionFactory->create();

        foreach ($data as $locationBalance) {
            $locationBalanceCollection->add(
                $this->locationBalanceBuilder->buildFromArray($locationBalance)
            );
        }

        return $locationBalanceCollection;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(LocationBalanceCollection $locationBalanceCollection): array
    {
        $data = [];

        foreach ($locationBalanceCollection->all() as $locationBalance) {
            $data[][] = $this->locationBalanceBuilder->createDataArray($locationBalance);
        }

        return $data;
    }
}
