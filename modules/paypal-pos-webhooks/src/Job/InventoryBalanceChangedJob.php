<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Toggle;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\SyncStockJob;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
class InventoryBalanceChangedJob implements Job
{
    const TYPE = 'webhook-inventory-balance-changed';
    /**
     * @var Toggle
     */
    private $toggle;
    /**
     * @var callable
     */
    private $createJobRecord;
    /**
     * InventoryBalanceChangedJob constructor.
     *
     * @param Toggle $toggle Needed to tell WcEvents that we want to update products in silence
     * @param callable $createJobRecord
     */
    public function __construct(Toggle $toggle, callable $createJobRecord)
    {
        $this->toggle = $toggle;
        $this->createJobRecord = $createJobRecord;
    }
    /**
     * @inheritDoc
     */
    public function execute(ContextInterface $context, JobRepository $repository, LoggerInterface $logger): bool
    {
        $this->toggle->disable();
        $localId = $context->args()->localId;
        $change = $context->args()->change;
        $wcProduct = wc_get_product($localId);
        if (!$wcProduct) {
            $logger->warning("WC Product {$localId} not found. Can't change the balance in InventoryBalanceChangedJob.");
            return \false;
        }
        $managedById = (int) $wcProduct->get_stock_managed_by_id();
        $currentStock = $wcProduct->get_stock_quantity();
        /**
         * Query instead of update_post_meta
         *
         * @see wc_update_product_stock
         */
        wc_update_product_stock($managedById, $currentStock + $change, 'set', \false);
        /**
         * This will force the data to be re-evaluated the next time the product is queried
         *
         * @see wc_delete_product_transients
         */
        wc_delete_product_transients($managedById);
        $this->toggle->enable();
        /**
         * Other variations with stock managed by parent will not be updated at iZ,
         * unless we specifically re-sync here
         */
        $repository->add(($this->createJobRecord)(SyncStockJob::TYPE, [
            'productId' => $managedById,
            'change' => $currentStock + $change,
            'oldStock' => $currentStock,
            /**
             * This transition should be: from STORE to BIN,
             * because we have not actually sold them, just got discarded
             */
            'frontOffice' => \false,
        ]));
        return \true;
    }
    public function isUnique(): bool
    {
        return \true;
    }
    public function type(): string
    {
        return self::TYPE;
    }
}
