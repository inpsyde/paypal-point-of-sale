<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseHistory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseHistoryFactory;

class PurchaseHistoryBuilder implements PurchaseHistoryBuilderInterface
{
    private PurchaseHistoryFactory $purchaseHistoryFactory;

    private PurchaseCollectionBuilderInterface $purchaseCollectionBuilder;

    /**
     * PurchaseHistoryBuilder constructor.
     * @param PurchaseHistoryFactory $purchaseHistoryFactory
     * @param PurchaseCollectionBuilderInterface $purchaseCollectionBuilder
     */
    public function __construct(
        PurchaseHistoryFactory $purchaseHistoryFactory,
        PurchaseCollectionBuilderInterface $purchaseCollectionBuilder
    ) {

        $this->purchaseHistoryFactory = $purchaseHistoryFactory;
        $this->purchaseCollectionBuilder = $purchaseCollectionBuilder;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(PurchaseHistory $purchase): array
    {
        return [
            'firstPurchaseHash' => $purchase->firstPurchaseHash(),
            'lastPurchaseHash' => $purchase->lastPurchaseHash(),
            'purchases' => $this->purchaseCollectionBuilder->createDataArray(
                $purchase->purchases()
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PurchaseHistory
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return PurchaseHistory
     */
    private function build(array $data): PurchaseHistory
    {
        return $this->purchaseHistoryFactory->create(
            $data['firstPurchaseHash'],
            $data['lastPurchaseHash'],
            $this->purchaseCollectionBuilder->buildFromArray(
                $data['purchases']
            )
        );
    }
}
