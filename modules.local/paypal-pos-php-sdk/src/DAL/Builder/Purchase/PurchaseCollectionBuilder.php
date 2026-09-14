<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseCollectionFactory;

class PurchaseCollectionBuilder implements PurchaseCollectionBuilderInterface
{
    private PurchaseCollectionFactory $purchaseCollectionFactory;

    private PurchaseBuilderInterface $purchaseBuilder;

    /**
     * PurchaseCollectionBuilder constructor.
     *
     * @param PurchaseCollectionFactory $purchaseCollectionFactory
     * @param PurchaseBuilderInterface $purchaseBuilder
     */
    public function __construct(
        PurchaseCollectionFactory $purchaseCollectionFactory,
        PurchaseBuilderInterface $purchaseBuilder
    ) {

        $this->purchaseCollectionFactory = $purchaseCollectionFactory;
        $this->purchaseBuilder = $purchaseBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PurchaseCollection
    {
        $purchaseCollection = $this->purchaseCollectionFactory->create();

        foreach ($data as $purchase) {
            $purchaseCollection->add(
                $this->purchaseBuilder->buildFromArray($purchase)
            );
        }

        return $purchaseCollection;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(PurchaseCollection $purchaseCollection): array
    {
        $data = [];

        /** @var Purchase $purchase */
        foreach ($purchaseCollection->all() as $purchase) {
            $data[][] = $this->purchaseBuilder->createDataArray($purchase);
        }

        return $data;
    }
}
