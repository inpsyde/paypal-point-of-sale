<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Cli;

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Psr\Log\LoggerInterface;

class ExcludeCommand
{
    /**
     * @var Job
     */
    private $deleteProductJob;

    /**
     * @var Job
     */
    private $unlinkProductJob;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ExcludeCommand constructor.
     *
     * @param Job $deleteProductJob
     * @param Job $unlinkProductJob
     * @param LoggerInterface $logger
     */
    public function __construct(
        Job $deleteProductJob,
        Job $unlinkProductJob,
        LoggerInterface $logger
    ) {
        $this->deleteProductJob = $deleteProductJob;
        $this->unlinkProductJob = $unlinkProductJob;
        $this->logger = $logger;
    }

    /**
     * Exclude a product locally and remotely at the Zettle Backoffice
     *
     * ## OPTIONS
     *
     * <id>
     * : The WC_Product ID
     *
     * ## EXAMPLES
     *
     *     wp zettle exclude product
     *
     * @when after_wp_load
     */
    public function product(array $args, array $assocArgs)
    {
        $productId = (int) $args[0];

        $this->deleteProductJob->execute(
            Context::fromArray(
                [
                    'productId' => $productId,
                ]
            ),
            new EphemeralJobRepository(),
            $this->logger
        );

        $this->unlinkProductJob->execute(
            Context::fromArray(
                [
                    'localId' => $productId,
                ]
            ),
            new EphemeralJobRepository(),
            $this->logger
        );
    }
}
