<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Handler;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Components\TermManager;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\DeleteProductJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\SetInventoryTrackingJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job\UnlinkProductJob;
class ProductExcludeHandler
{
    private ProductRepositoryInterface $repository;
    private TermManager $termManager;
    /**
     * @var callable
     */
    private $createJob;
    private JobRepository $jobRepository;
    /**
     * ProductExcludeHandler constructor.
     *
     * @param ProductRepositoryInterface $repository
     * @param TermManager $termManager
     * @param JobRepository $jobRepository
     * @param callable $createJob
     */
    public function __construct(ProductRepositoryInterface $repository, TermManager $termManager, JobRepository $jobRepository, callable $createJob)
    {
        $this->repository = $repository;
        $this->termManager = $termManager;
        $this->jobRepository = $jobRepository;
        $this->createJob = $createJob;
    }
    /**
     * Check if a Product is valid and doesn't have the zettle_exclude term related
     *
     * @param int $objectId
     * @param string $taxonomy
     * @param int ...$termIds
     *
     * @return bool
     */
    public function isIncludable(int $objectId, string $taxonomy, int ...$termIds): bool
    {
        if (!$this->acceptsProduct($objectId)) {
            return \false;
        }
        if (!$this->hasTerm($taxonomy, ...$termIds)) {
            return \false;
        }
        return \true;
    }
    /**
     * Check if the Product is valid and has the zettle_excluded term related
     *
     * @param int $objectId
     * @param string $taxonomy
     * @param int ...$termIds
     *
     * @return bool
     */
    public function isExcludable(int $objectId, string $taxonomy, int ...$termIds): bool
    {
        if (!$this->acceptsProduct($objectId)) {
            return \false;
        }
        if ($this->hasTerm($taxonomy, ...$termIds)) {
            return \false;
        }
        return \true;
    }
    /**
     * @param int $objectId
     *
     * @return bool
     */
    public function acceptsProduct(int $objectId): bool
    {
        $product = $this->repository->findById($objectId);
        if ($product === null) {
            return \false;
        }
        if ($product->is_type(ProductType::VARIATION)) {
            return \false;
        }
        return \true;
    }
    /**
     * Handle a Product that will be excluded - term was added
     *
     * @param int $productId
     */
    public function excludeProduct(int $productId): void
    {
        $product = $this->repository->findById($productId);
        if ($product === null) {
            return;
        }
        $jobs = [];
        if ($product->managing_stock()) {
            $jobs[] = ($this->createJob)(SetInventoryTrackingJob::TYPE, ['productId' => $productId, 'state' => \false]);
        }
        $jobs[] = ($this->createJob)(DeleteProductJob::TYPE, ['productId' => $productId]);
        $jobs[] = ($this->createJob)(UnlinkProductJob::TYPE, ['localId' => $productId]);
        $this->jobRepository->add(...$jobs);
    }
    /**
     * Handle a Product that will be included - term was removed
     *
     * @param int $productId
     */
    public function includeProduct(int $productId): void
    {
        $product = $this->repository->findById($productId);
        if ($product === null) {
            return;
        }
        $this->jobRepository->add(($this->createJob)(ExportProductJob::TYPE, ['productId' => $productId]));
    }
    /**
     * @param string $taxonomy
     * @param int ...$termIds
     *
     * @return bool
     */
    protected function hasTerm(string $taxonomy, int ...$termIds): bool
    {
        if (!in_array($this->termManager->id(), $termIds, \true)) {
            return \false;
        }
        if ($taxonomy !== $this->termManager->taxonomy()) {
            return \false;
        }
        return \true;
    }
}
