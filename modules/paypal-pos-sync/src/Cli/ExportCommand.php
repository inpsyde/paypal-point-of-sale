<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Cli;

use Syde\Vendor\Zettle\Inpsyde\Queue\Processor\QueueProcessor;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\EnqueueProductSyncJob;
class ExportCommand
{
    private QueueProcessor $processor;
    /**
     * @var callable
     */
    private $createJobRecord;
    public function __construct(QueueProcessor $processor, callable $createJobRecord)
    {
        $this->processor = $processor;
        $this->createJobRecord = $createJobRecord;
    }
    /**
     * Deletes all Zettle products and clears WooCommerce of all connections
     *
     * ## EXAMPLES
     *
     *     wp zettle export products
     *
     * @when after_wp_load
     */
    public function products(array $args, array $assocArgs): void
    {
        $this->processor->repository()->add(($this->createJobRecord)(EnqueueProductSyncJob::TYPE));
        $this->processor->process();
    }
}
