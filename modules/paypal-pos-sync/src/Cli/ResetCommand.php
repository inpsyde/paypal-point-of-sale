<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Cli;

use Syde\Vendor\Zettle\Inpsyde\Queue\Db\Table as QueueTable;
use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\QueueRuntimeException;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Context;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DB\Table as IdMapTable;
use Syde\Vendor\Zettle\WP_CLI;
class ResetCommand
{
    private IdMapTable $idMapTable;
    private QueueTable $queueTable;
    private Job $wipeRemoteJob;
    private LoggerInterface $logger;
    public function __construct(Job $wipeRemoteJob, LoggerInterface $logger, IdMapTable $idMapTable, QueueTable $queueTable)
    {
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
    public function products(array $args, array $assocArgs): void
    {
        if (!(isset($assocArgs['noconfirm']) && $assocArgs['noconfirm'] === \true)) {
            WP_CLI::log("This command will delete ALL PayPal Point of Sale products in your merchant account.");
            WP_CLI::log("It will also delete any connections in your WooCommerce install.");
            WP_CLI::confirm("Are you sure you want to do this");
        }
        $this->wipeRemoteJob->execute(Context::fromArray([]), new EphemeralJobRepository(), $this->logger);
        global $wpdb;
        foreach ([$this->idMapTable->name(), $this->queueTable->name()] as $tableName) {
            $prefix = $wpdb->get_blog_prefix();
            $wpdb->query($wpdb->prepare('TRUNCATE TABLE %s%s;', $prefix, $tableName));
            WP_CLI::log("Emptied table '{$tableName}'");
        }
    }
}
