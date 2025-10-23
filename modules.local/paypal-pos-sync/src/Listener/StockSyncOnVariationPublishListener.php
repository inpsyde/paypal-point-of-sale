<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Syde\PayPal\PointOfSale\Sync\Job\SyncStockJob;
use WC_Product;
use WC_Product_Variation;

/**
 * Enabling an inactive variation requires a stock sync since the variant was deleted at iZ.
 * But since merely enabling a variation will not trigger stock change listeners,
 * we explicitly force a stock sync here
 * @see ProductEventListenerRegistry::onPublish()
 */
class StockSyncOnVariationPublishListener
{

    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var callable
     */
    private $isSyncable;

    /**
     * VariationPublishListener constructor.
     *
     * @param callable $createJob
     * @param callable $isSyncable
     */
    public function __construct(
        callable $createJob,
        callable $isSyncable
    ) {
        $this->createJob = $createJob;
        $this->isSyncable = $isSyncable;
    }

    /**
     * @param WC_Product_Variation $new
     * @param WC_Product $old
     */
    public function __invoke(WC_Product_Variation $new, WC_Product $old): void
    {
        $productId = (int) $new->get_id();

        if (!($this->isSyncable)($productId)) {
            return;
        }
        ($this->createJob)(
            SyncStockJob::TYPE,
            [
                'productId' => $productId,
                'change' => $new->get_stock_quantity(),
                'oldStock' => 0,
                'frontOffice' => false,
            ]
        );
    }
}
