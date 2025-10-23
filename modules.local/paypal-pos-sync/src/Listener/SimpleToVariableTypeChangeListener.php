<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\PayPal\PointOfSale\Sync\Job\ReExportProductJob;
use WC_Product_Simple;
use WC_Product_Variable;

/**
 * @see ProductEventListenerRegistry::onTypeChange()
 */
class SimpleToVariableTypeChangeListener
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

    public function __invoke(WC_Product_Variable $new, WC_Product_Simple $old): void
    {
        $productId = (int) $new->get_id();

        if (!($this->isSyncable)($productId)) {
            return;
        }

        ($this->createJob)(
            ReExportProductJob::TYPE,
            [
                'productId' => $productId,
                // pass now-obsolete ids so the id-map can be cleaned up
                'variationIds' => [$productId],
            ]
        );
    }
}
