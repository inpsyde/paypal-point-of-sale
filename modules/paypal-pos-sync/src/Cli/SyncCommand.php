<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Cli;

use Syde\Vendor\Zettle\Inpsyde\Queue\Processor\QueueProcessor;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
class SyncCommand
{
    /**
     * @var QueueProcessor
     */
    private $processor;
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
     * Sync a single product
     *
     * ## OPTIONS
     *
     * <id>
     * : The WC_Product ID
     *
     * ## EXAMPLES
     *
     *     wp zettle sync product
     *
     * @when after_wp_load
     */
    public function product(array $args, array $assocArgs)
    {
        $this->processor->repository()->add(($this->createJobRecord)(ExportProductJob::TYPE, ['productId' => (int) $args[0]]));
        $this->processor->process();
    }
}
