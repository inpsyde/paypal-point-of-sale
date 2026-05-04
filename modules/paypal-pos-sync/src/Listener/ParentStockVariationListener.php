<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\SyncStockJob;
use WC_Product_Variable;
use WC_Product_Variation;
/**
 * This Listener handles an edge-case when a new WC_Product_Variation is added
 * to a variable product with parent-level inventory tracking.
 * The variation could be synced without a stock quantity because the parent never
 * received a stock quantity change event and the new variation will not trigger one either:
 * Because it does not have its own inventory
 *
 * @see ProductEventListenerRegistry::onChange()
 */
class ParentStockVariationListener
{
    /**
     * @var callable
     */
    private $createJob;
    /**
     * @var callable(int):bool
     */
    private $isSyncable;
    public function __construct(callable $createJob, callable $isSyncable)
    {
        $this->createJob = $createJob;
        $this->isSyncable = $isSyncable;
    }
    public function __invoke(WC_Product_Variation $new): void
    {
        $parentId = (int) $new->get_parent_id();
        $parentProduct = new WC_Product_Variable($parentId);
        /** Check if Product and Variation is Syncable */
        if (!($this->isSyncable)((int) $new->get_id())) {
            return;
        }
        /**
         * Only execute the ensure logic, if we deal with a variation that
         * got the stock from the parent product
         */
        if (!$this->isManagingStockByParent($new)) {
            return;
        }
        $this->ensureStockParentProduct($parentProduct);
    }
    /**
     * Update the Stock for the ParentProduct
     *
     * @param WC_Product_Variable $parentProduct
     */
    protected function ensureStockParentProduct(WC_Product_Variable $parentProduct): void
    {
        ($this->createJob)(SyncStockJob::TYPE, ['productId' => (int) $parentProduct->get_id(), 'change' => (int) $parentProduct->get_stock_quantity(), 'oldStock' => 0, 'frontOffice' => \false]);
    }
    /**
     * Determine if the Variation manage stock by themself or from the parent
     *
     * @param WC_Product_Variation $variation
     *
     * @return bool
     */
    private function isManagingStockByParent(WC_Product_Variation $variation): bool
    {
        if ((int) $variation->get_stock_managed_by_id() === (int) $variation->get_id()) {
            return \false;
        }
        $product = wc_get_product((int) $variation->get_parent_id());
        if (!$product) {
            return \false;
        }
        if (!$product->is_type(ProductType::VARIABLE)) {
            return \false;
        }
        if (!$product->managing_stock()) {
            return \false;
        }
        return \true;
    }
}
