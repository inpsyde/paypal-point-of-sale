<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\SyncStockJob;
use WC_Product;
/**
 * @see ProductEventListenerRegistry::onPropertyChange() with 'stock_quantity'
 */
class StockQuantityListener
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
    public function __invoke(WC_Product $new, WC_Product $old): void
    {
        $productId = (int) $new->get_id();
        if (!($this->isSyncable)($productId)) {
            return;
        }
        $oldStock = $old->get_stock_quantity();
        $newStock = $new->get_stock_quantity();
        $diff = $newStock - $oldStock;
        if ($diff === 0) {
            return;
        }
        ($this->createJob)(SyncStockJob::TYPE, ['productId' => $productId, 'change' => $diff, 'oldStock' => $oldStock, 'frontOffice' => $this->isFrontOffice()]);
    }
    /**
     * @return bool
     */
    private function isFrontOffice(): bool
    {
        if (is_admin()) {
            return \false;
        }
        if (defined('Syde\Vendor\Zettle\WP_CLI') && WP_CLI) {
            return \false;
        }
        if (defined('REST_REQUEST')) {
            return \false;
        }
        return \true;
    }
}
