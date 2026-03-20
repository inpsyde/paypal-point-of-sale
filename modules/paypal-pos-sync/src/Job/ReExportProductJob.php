<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\VariableInventoryChecker;
use InvalidArgumentException;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use WC_Product;
use WC_Product_Variable;
class ReExportProductJob implements Job
{
    use VariableInventoryChecker;
    public const TYPE = 're-export-product';
    /**
     * @var ProductRepositoryInterface
     */
    private $repository;
    /**
     * @var WcProductRepositoryInterface
     */
    private $wcRepository;
    /**
     * @var OneToOneMapInterface|MapRecordCreator
     */
    private $variantMap;
    /**
     * @var callable
     */
    private $createJobRecord;
    /**
     * ProductTypeChangeJob constructor.
     *
     * @param ProductRepositoryInterface $repository
     * @param WcProductRepositoryInterface $wcRepository
     * @param OneToOneMapInterface $variantMap
     * @param callable $createJobRecord
     */
    public function __construct(ProductRepositoryInterface $repository, WcProductRepositoryInterface $wcRepository, OneToOneMapInterface $variantMap, callable $createJobRecord)
    {
        if (!$variantMap instanceof MapRecordCreator) {
            throw new InvalidArgumentException(sprintf('Expected ID-Map of type %s to implement %s.', get_class($variantMap), MapRecordCreator::class));
        }
        $this->repository = $repository;
        $this->wcRepository = $wcRepository;
        $this->variantMap = $variantMap;
        $this->createJobRecord = $createJobRecord;
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
    /**
     * @inheritDoc
     */
    public function execute(ContextInterface $context, JobRepository $repository, LoggerInterface $logger): bool
    {
        $productId = (int) $context->args()->productId;
        $variationIds = (array) $context->args()->variationIds;
        // TODO: Debug and maybe we need to fetch the product from the DB with DataStoreFactory
        $product = $this->wcRepository->findById($productId);
        if ($product === null) {
            $logger->error("ProductTypeChanged: Product with Id: {$productId} not found.");
            return \false;
        }
        if ($this->repository->findById($productId) !== null) {
            $this->removeProduct($product, $repository);
        }
        $this->exportProduct($product, $variationIds, $repository, $logger);
        return \true;
    }
    /**
     * @param WC_Product $product
     * @param JobRepository $repository
     *
     * @return bool
     */
    public function removeProduct(WC_Product $product, JobRepository $repository): bool
    {
        $jobs = [];
        $productId = (int) $product->get_id();
        if ($this->tracksInventory($product)) {
            $jobs[] = ($this->createJobRecord)(SetInventoryTrackingJob::TYPE, ['productId' => $productId, 'state' => \false]);
        }
        $jobs[] = ($this->createJobRecord)(DeleteProductJob::TYPE, ['productId' => $productId]);
        $repository->add(...$jobs);
        return \true;
    }
    /**
     * @param WC_Product $product
     * @param int[] $variationIds
     * @param JobRepository $repository
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    public function exportProduct(WC_Product $product, array $variationIds, JobRepository $repository, LoggerInterface $logger): bool
    {
        $productId = (int) $product->get_id();
        // Update Variations - remove old variations <-> variant mapping
        $updated = $this->unmapVariationIds($variationIds);
        if (!$updated) {
            $variations = implode(', ', $variationIds);
            $logger->debug(sprintf("ProductTypeChange: Couldn't update variants %s mapping for Product: %s", $variations, $productId));
        }
        $repository->add(($this->createJobRecord)(ExportProductJob::TYPE, ['productId' => $productId]));
        return \true;
    }
    /**
     * @param int[] $variationIds
     *
     * @return bool
     */
    private function unmapVariationIds(array $variationIds): bool
    {
        if (empty($variationIds)) {
            return \false;
        }
        $deletedVariations = [];
        foreach ($variationIds as $variationId) {
            try {
                $this->variantMap->deleteRecord($variationId, $this->variantMap->remoteId($variationId));
                $deletedVariations[] = $variationId;
            } catch (IdNotFoundException $exception) {
                continue;
            }
        }
        return !empty($deletedVariations);
    }
    /**
     * @param WC_Product $product
     *
     * @return bool
     */
    private function tracksInventory(WC_Product $product): bool
    {
        if ((bool) $product->managing_stock()) {
            return \true;
        }
        if ($product instanceof WC_Product_Variable && $this->hasStockManagingVariations($product)) {
            return \true;
        }
        return \false;
    }
}
