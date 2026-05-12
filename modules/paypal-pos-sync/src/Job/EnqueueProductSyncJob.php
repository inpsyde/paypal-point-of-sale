<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductState;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
/**
 * Class EnqueueProductSyncJob
 *
 * Fetches a list of all relevant products and inserts a sync job for them
 *
 * @package Syde\PayPal\PointOfSale\Sync\Job
 */
class EnqueueProductSyncJob implements Job
{
    public const TYPE = 'enqueue-products-to-sync';
    private array $productTypeWhitelist;
    /**
     * @var callable
     */
    private $createJobRecord;
    /**
     * @var callable
     */
    private $productCanSynced;
    /**
     * EnqueueProductSyncJob constructor.
     *
     * @param array $productTypeWhitelist
     * @param callable $createJobRecord
     * @param callable $productCanSynced
     */
    public function __construct(array $productTypeWhitelist, callable $createJobRecord, callable $productCanSynced)
    {
        $this->productTypeWhitelist = $productTypeWhitelist;
        $this->createJobRecord = $createJobRecord;
        $this->productCanSynced = $productCanSynced;
    }
    /**
     * @inheritDoc
     */
    public function execute(ContextInterface $context, JobRepository $repository, LoggerInterface $logger): bool
    {
        $products = wc_get_products(['return' => 'ids', 'limit' => -1, 'status' => ProductState::PUBLISH, 'type' => $this->productTypeWhitelist]);
        if (!is_array($products)) {
            return \true;
        }
        $jobs = [];
        foreach ($products as $product) {
            if (($this->productCanSynced)($product)) {
                $jobs[] = ($this->createJobRecord)(ExportProductJob::TYPE, ['productId' => $product]);
            }
        }
        /**
         * We should consider using array_chunk here.
         * Massive amounts of products might break a very
         * large insert call
         */
        $repository->add(...$jobs);
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function isUnique(): bool
    {
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::TYPE;
    }
}
