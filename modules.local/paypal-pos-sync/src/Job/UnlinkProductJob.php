<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use WC_Product;
use WC_Product_Variable;

class UnlinkProductJob implements Job
{
    public const TYPE = 'unlink-product';

    private MapRecordCreator|OneToOneMapInterface $productIdMap;

    private MapRecordCreator|OneToManyMapInterface $variantIdMap;

    private ProductRepositoryInterface $repository;

    /**
     * UnlinkProductJob constructor.
     *
     * @param OneToOneMapInterface $productIdMap
     * @param OneToManyMapInterface $variantIdMap
     * @param ProductRepositoryInterface $repository
     */
    public function __construct(
        OneToOneMapInterface $productIdMap,
        OneToManyMapInterface $variantIdMap,
        ProductRepositoryInterface $repository
    ) {

        $this->productIdMap = $productIdMap;
        $this->variantIdMap = $variantIdMap;
        $this->repository = $repository;
    }

    public function isUnique(): bool
    {
        return true;
    }

    public function type(): string
    {
        return self::TYPE;
    }

    /**
     * @param ContextInterface $context
     * @param JobRepository $repository
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        if (!isset($context->args()->localId) && !isset($context->args()->remoteId)) {
            $logger->error('Cannot unlink Product: No valid local Id and remoteId was given.');

            return true;
        }

        /**
         * This job currently does not clean up image records.
         * An image might be used for more than one product and we have no way of knowing this
         */
        if (isset($context->args()->localId)) {
            try {
                $this->removeFromLocalId(
                    (int) $context->args()->localId,
                    $logger
                );
                $this->logSuccess($logger, (string) $context->args()->localId);

                return true;
            } catch (IdNotFoundException $exception) {
                $logger->warning($exception->getMessage());

                return false;
            }
        }

        if (isset($context->args()->remoteId)) {
            try {
                $this->removeFromRemoteId(
                    (string) $context->args()->remoteId,
                    $logger
                );
                $this->logSuccess($logger, (string) $context->args()->remoteId);

                return true;
            } catch (IdNotFoundException $exception) {
                $logger->warning($exception->getMessage());

                return false;
            }
        }

        return true;
    }

    private function logSuccess(LoggerInterface $logger, string $id)
    {
        $logger->info(
            sprintf(
                'ID-mapping cleared for product %s',
                $id
            )
        );
    }

    /**
     * @param int $localId
     * @param LoggerInterface $logger
     *
     * @throws IdNotFoundException
     */
    private function removeFromLocalId(int $localId, LoggerInterface $logger): void
    {
        $remoteId = $this->productIdMap->remoteId($localId);

        $this->productIdMap->deleteRecord($localId, $remoteId);
        $logger->info("Deleted product mapping {$localId} <-> {$remoteId}");

        try {
            $this->cleanupVariants($localId, $logger);
        } catch (IdNotFoundException $exception) {
            $logger->error("Couldn't remove Variants because of: {$exception->getMessage()}");
        }
    }

    /**
     * @param string $remoteId
     * @param LoggerInterface $logger
     *
     * @throws IdNotFoundException
     */
    private function removeFromRemoteId(string $remoteId, LoggerInterface $logger): void
    {
        $localId = $this->productIdMap->localId($remoteId);

        $this->productIdMap->deleteRecord($localId, $remoteId);
        $logger->info("Deleted product mapping {$localId} <-> {$remoteId}");

        try {
            $this->cleanupVariants($localId, $logger);
        } catch (IdNotFoundException $exception) {
            $logger->error("Couldn't remove Variants because of: {$exception->getMessage()}");
        }
    }

    /**
     * @param int $localId
     * @param LoggerInterface $logger
     *
     * @throws IdNotFoundException
     */
    private function cleanupVariants(int $localId, LoggerInterface $logger): void
    {
        $wcProduct = $this->repository->findById($localId);

        if ($wcProduct === null) {
            $logger->error("Could not unlink and find product by given ID: {$localId}");

            return;
        }

        $localVariantIds = $this->variantIds($wcProduct);

        foreach ($localVariantIds as $localVariantId) {
            $remoteIds = $this->variantIdMap->remoteIds($localVariantId);

            foreach ($remoteIds as $remoteId) {
                $this->variantIdMap->deleteRecord($localVariantId, $remoteId);
                $logger->info("Deleted variant mapping {$localVariantId} <-> {$remoteId}");
            }
        }
    }

    private function variantIds(WC_Product $product): array
    {
        if ($product instanceof WC_Product_Variable) {
            return (array) $product->get_visible_children();
        }

        return [$product->get_id()];
    }
}
