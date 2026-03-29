<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\PayPal\PointOfSale\Sync\Job\SetInventoryTrackingJob;
use WC_Product_Simple;
use WC_Product_Variation;

/**
 * Runs whenever a simple product has changed its 'managing_stock' property
 * @see ProductEventListenerRegistry::onPropertyChange() with 'managing_stock'
 */
class SimpleManageStockListener
{
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

    public function __invoke(WC_Product_Simple $new): void
    {
        if ($new instanceof WC_Product_Variation) {
            return;
        }

        $productId = (int) $new->get_id();

        if (!($this->isSyncable)($productId)) {
            return;
        }

        ($this->createJob)(
            SetInventoryTrackingJob::TYPE,
            [
                'productId' => $productId,
                'state' => (bool) $new->managing_stock(),
            ]
        );
    }
}
