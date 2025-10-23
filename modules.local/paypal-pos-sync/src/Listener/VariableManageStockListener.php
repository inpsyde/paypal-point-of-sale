<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\PayPal\PointOfSale\Sync\Job\SetInventoryTrackingJob;
use Syde\PayPal\PointOfSale\Sync\VariableInventoryChecker;
use WC_Product_Variable;

/**
 * @see ProductEventListenerRegistry::onPropertyChange() with 'managing_stock'
 */
class VariableManageStockListener
{
    use VariableInventoryChecker;

    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var callable(int):bool
     */
    private $isSyncable;

    public function __construct(
        callable $createJob,
        callable $isSyncable
    ) {

        $this->createJob = $createJob;
        $this->isSyncable = $isSyncable;
    }

    public function __invoke(WC_Product_Variable $new): void
    {
        $productId = (int) $new->get_id();

        if (!($this->isSyncable)($productId)) {
            return;
        }

        ($this->createJob)(
            SetInventoryTrackingJob::TYPE,
            [
                'productId' => $productId,
                'state' => (bool) $new->managing_stock()
                    || $this->hasStockManagingVariations($new),
            ]
        );
    }
}
