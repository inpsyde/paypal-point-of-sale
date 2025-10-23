<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Cli;

use Inpsyde\Queue\Db\Table as QueueTable;
use Inpsyde\Queue\Exception\QueueRuntimeException;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\DB\Table as IdMapTable;
use Psr\Log\LoggerInterface;
use WP_CLI;

class ResetCommand
{

    /**
     * @var Products
     */
    private $productsClient;

    /**
     * @var IdMapTable
     */
    private $idMapTable;

    /**
     * @var QueueTable
     */
    private $queueTable;

    /**
     * @var Job
     */
    private $wipeRemoteJob;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Job $wipeRemoteJob,
        LoggerInterface $logger,
        IdMapTable $idMapTable,
        QueueTable $queueTable
    ) {

        $this->wipeRemoteJob = $wipeRemoteJob;
        $this->logger = $logger;
        $this->idMapTable = $idMapTable;
        $this->queueTable = $queueTable;
    }

    /**
     * Deletes all Zettle products and clears WooCommerce of all connections
     *
     * [--noconfirm]
     * : Whether or not to ask for confirmation
     *
     * ## EXAMPLES
     *
     *     wp zettle reset products
     *
     * @when after_wp_load
     * @throws QueueRuntimeException
     */
    public function products(array $args, array $assocArgs)
    {
        if (!(isset($assocArgs['noconfirm']) && $assocArgs['noconfirm'] === true)) {
            WP_CLI::log("This command will delete ALL PayPal Point of Sale products in your merchant account.");
            WP_CLI::log("It will also delete any connections in your WooCommerce install.");
            WP_CLI::confirm(
                "Are you sure you want to do this"
            );
        }

        $this->wipeRemoteJob->execute(Context::fromArray([]), new EphemeralJobRepository(), $this->logger);
        global $wpdb;
        foreach (
            [
            $this->idMapTable->name(),
            $this->queueTable->name(),
            ] as $tableName
        ) {
            $prefix = $wpdb->get_blog_prefix();
            $wpdb->query(
                $wpdb->prepare(
                    'TRUNCATE TABLE %s%s;',
                    $prefix,
                    $tableName
                )
            );
            WP_CLI::log("Emptied table '{$tableName}'");
        }
    }
}
