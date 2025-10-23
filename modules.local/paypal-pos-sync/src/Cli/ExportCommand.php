<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Cli;

use Inpsyde\Queue\Processor\QueueProcessor;
use Syde\PayPal\PointOfSale\Sync\Job\EnqueueProductSyncJob;

class ExportCommand
{

    /**
     * @var QueueProcessor
     */
    private $processor;

    /**
     * @var callable
     */
    private $createJobRecord;

    public function __construct(
        QueueProcessor $processor,
        callable $createJobRecord
    ) {
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
    public function products(array $args, array $assocArgs)
    {
        $this->processor->repository()->add(
            ($this->createJobRecord)(EnqueueProductSyncJob::TYPE)
        );
        $this->processor->process();
    }
}
