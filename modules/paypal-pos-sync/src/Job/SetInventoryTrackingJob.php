<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\ExceptionLoggingTrait;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Inventory\Inventory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\LazyProduct;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
/**
 * Class SetInventoryTrackingJob
 *
 * This job enables or disables inventory tracking for a remote product.
 * If enabled, it will attempt to set the WooCommerce stock
 *
 * @package Syde\PayPal\PointOfSale\Sync\Job
 */
class SetInventoryTrackingJob implements Job
{
    use ExceptionLoggingTrait;
    public const TYPE = 'set-inventory-tracking';
    private ProductRepositoryInterface $repository;
    private Inventory $inventoryClient;
    private BuilderInterface $builder;
    private OneToManyMapInterface $variantMap;
    /**
     * SetInventoryTrackingJob constructor.
     *
     * @param ProductRepositoryInterface $repository
     * @param Inventory $inventoryClient
     * @param BuilderInterface $builder
     * @param OneToManyMapInterface $variantMap
     */
    public function __construct(ProductRepositoryInterface $repository, Inventory $inventoryClient, BuilderInterface $builder, OneToManyMapInterface $variantMap)
    {
        $this->repository = $repository;
        $this->inventoryClient = $inventoryClient;
        $this->builder = $builder;
        $this->variantMap = $variantMap;
    }
    /**
     * @inheritDoc
     */
    public function execute(ContextInterface $context, JobRepository $repository, LoggerInterface $logger): bool
    {
        $productId = (int) $context->args()->productId;
        $wcProduct = $this->repository->findById($productId);
        if ($wcProduct === null) {
            $logger->warning("Could not find WC Product with ID {$productId}");
            return \true;
        }
        if ($wcProduct->is_type(ProductType::VARIATION)) {
            $logger->warning("Stock management at variation level is not supported from PayPal Point of Sale. ID:{$productId}");
            return \true;
        }
        try {
            /**
             * PHPStorm does not see the possible ZettleRestException otherwise
             * @var LazyProduct $product
             */
            $product = $this->builder->build(ProductInterface::class, $wcProduct);
            $uuid = $product->uuid();
        } catch (ZettleRestException $exception) {
            $logger->error("Could not build Product for ID: {$productId}");
            $this->logException($exception, $logger);
            return \true;
        }
        $state = (bool) $context->args()->state;
        if ($state) {
            return $this->startTracking($uuid, $productId, $logger);
        }
        return $this->stopTracking($uuid, $productId, $product, $logger);
    }
    /**
     * @param string $uuid
     * @param int $productId
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    private function startTracking(string $uuid, int $productId, LoggerInterface $logger): bool
    {
        try {
            $this->inventoryClient->startTracking($uuid);
        } catch (ZettleRestException $exception) {
            $logger->error("Could not start Tracking of Product {$productId} | {$uuid}");
            $this->logException($exception, $logger);
            return \false;
        }
        $logger->info("Enabled inventory tracking for WC product {$productId} with UUID {$uuid}");
        return \true;
    }
    /**
     * @param string $uuid
     * @param int $productId
     * @param ProductInterface $product
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    private function stopTracking(string $uuid, int $productId, ProductInterface $product, LoggerInterface $logger): bool
    {
        try {
            $this->inventoryClient->stopTracking($product->uuid());
        } catch (ZettleRestException $exception) {
            $logger->error("Could not stop Tracking of Product {$productId} | {$uuid}");
            $this->logException($exception, $logger);
            return \true;
        }
        $logger->info("Disabled inventory tracking for WC product {$productId} with UUID {$uuid}");
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
