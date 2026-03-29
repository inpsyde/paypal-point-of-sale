<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\Sync\Job\DeleteProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\SetInventoryTrackingJob;
use Syde\PayPal\PointOfSale\Sync\VariableInventoryChecker;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Deletes a product from Zettle if it was depublished in WooCommerce
 *
 * @see ProductEventListenerRegistry::onDelete()
 * @see ProductEventListenerRegistry::onDraft()
 * @see ProductEventListenerRegistry::onTrash()
 */
class DePublishListener
{
    use VariableInventoryChecker;

    /**
     * @var callable
     */
    private $createJob;

    private OneToOneMapInterface $productMap;

    public function __construct(
        OneToOneMapInterface $productMap,
        callable $createJob
    ) {

        $this->createJob = $createJob;
        $this->productMap = $productMap;
    }

    public function __invoke(WC_Product $product): void
    {
        if ($product instanceof WC_Product_Variation) {
            return;
        }

        /**
         * No need to take action here if the product does not even exist.
         */
        if (!$this->hasBeenSynced($product)) {
            return;
        }

        $productId = (int) $product->get_id();

        if ($this->productTracksInventory($product)) {
            ($this->createJob)(
                SetInventoryTrackingJob::TYPE,
                [
                    'productId' => $productId,
                    'state' => false,
                ]
            );
        }

        ($this->createJob)(
            DeleteProductJob::TYPE,
            [
                'productId' => $productId,
            ]
        );
    }

    /**
     * Checks the ID-Map for a remote ID
     *
     * @param WC_Product $product
     *
     * @return bool
     */
    protected function hasBeenSynced(WC_Product $product): bool
    {
        try {
            $this->productMap->remoteId($product->get_id());
        } catch (IdNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the product is supposed to have inventory tracking on iZ
     *
     * @param WC_Product $product
     *
     * @return bool
     */
    private function productTracksInventory(WC_Product $product): bool
    {
        if ($product->managing_stock() && $product->managing_stock() !== 'parent') {
            return true;
        }

        if (
            $product instanceof WC_Product_Variable
            && $this->hasStockManagingVariations($product)
        ) {
            return true;
        }

        return false;
    }
}
