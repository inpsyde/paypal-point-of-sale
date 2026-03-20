<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Listener;

use WC_Product;
/**
 * Detects if a product has somehow become invalid.
 * Then it will enqueue the necessary jobs to remove it from Zettle
 * @see ProductEventListenerRegistry::onChange()
 */
class NotSyncableListener
{
    /**
     * @var DePublishListener
     */
    private $depublishListener;
    /**
     * @var callable(int):bool
     */
    private $isSyncable;
    /**
     * @param callable(int):bool $isSyncable A function accepting a WC product ID
     * and returning whether it can be synced (valid).
     */
    public function __construct(DePublishListener $depublishListener, callable $isSyncable)
    {
        $this->depublishListener = $depublishListener;
        $this->isSyncable = $isSyncable;
    }
    public function __invoke(WC_Product $new, WC_Product $old): void
    {
        /**
         * Can the new product still be synced?
         * Then there's nothing to do...
         */
        if (($this->isSyncable)((int) $new->get_id())) {
            return;
        }
        ($this->depublishListener)($new);
    }
}
