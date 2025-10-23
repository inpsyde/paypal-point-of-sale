<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\UnlinkVariantJob;
use WC_Product_Variation;

/**
 * @see ProductEventListenerRegistry::onDelete()
 */
class VariationDeleteListener
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

    /**
     * @param WC_Product_Variation $product
     */
    public function __invoke(WC_Product_Variation $product): void
    {
        $productId = (int) $product->get_id();
        $parentProductId = (int) $product->get_parent_id();

        if (!($this->isSyncable)($parentProductId)) {
            return;
        }

        ($this->createJob)(
            UnlinkVariantJob::TYPE,
            [
                'variationId' => $productId,
            ]
        );

        ($this->createJob)(
            ExportProductJob::TYPE,
            [
                'productId' => $parentProductId,
            ]
        );
    }
}
