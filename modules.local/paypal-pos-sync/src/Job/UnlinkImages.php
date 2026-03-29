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
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Class UnlinkImages
 *
 * Unlink images at the IdMap locally
 *
 * @package Syde\PayPal\PointOfSale\Sync\Job
 */
class UnlinkImages implements Job
{
    public const TYPE = 'unlink-images';

    public const PRODUCT_TYPE = 'product';

    public const VARIANT_TYPE = 'variant';

    private MapRecordCreator|OneToManyMapInterface $imageIdMap;

    private ProductRepositoryInterface $repository;

    /**
     * UnlinkProductJob constructor.
     *
     * @param OneToManyMapInterface $imageIdMap
     * @param ProductRepositoryInterface $repository
     */
    public function __construct(
        OneToManyMapInterface $imageIdMap,
        ProductRepositoryInterface $repository
    ) {

        $this->imageIdMap = $imageIdMap;
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        $productId = isset($context->args()->productId) ? (int) $context->args()->productId : 0;

        if ($productId !== 0 && !$this->fromProductId($productId, $logger)) {
            $logger->error("Not able to unlink images for product with ID: {$productId}");

            return false;
        }

        $attachmentId = isset($context->args()->attachmentId) ? (int) $context->args()->attachmentId : 0;
        $type = isset($context->args()->type) ? (string) $context->args()->type : '';

        if (
            $attachmentId !== 0
            && !empty($type)
            && !$this->fromAttachmentId($attachmentId, $type, $logger)
        ) {
            $logger->error("Not able to unlink image for {$type} with attachment ID: {$attachmentId}");

            return false;
        }

        return true;
    }

    /**
     * @param int $productId
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    protected function fromProductId(int $productId, LoggerInterface $logger): bool
    {
        if ($productId === 0) {
            return false;
        }

        $this->removeFromProductId($productId, $logger);

        return true;
    }

    /**
     * @param int $attachmentId
     * @param string $type
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    protected function fromAttachmentId(int $attachmentId, string $type, LoggerInterface $logger): bool
    {
        $typeModified = ucfirst($type);

        try {
            $remoteId = $this->imageIdMap->remoteId($attachmentId);
        } catch (IdNotFoundException $exception) {
            $logger->error("Could not found {$typeModified} Image {$attachmentId}");

            return false;
        }

        $this->cleanupImage($type, $attachmentId, $remoteId, $logger);

        return true;
    }

    /**
     * @param int $productId
     * @param LoggerInterface $logger
     */
    private function removeFromProductId(int $productId, LoggerInterface $logger): void
    {
        $product = $this->repository->findById($productId);

        if ($product === null) {
            $logger->error("Could not find image for Product: {$productId}");

            return;
        }

        if (!$product->is_purchasable()) {
            $logger->error("Product is not purchasable could not find image for Product: {$productId}");

            return;
        }

        if ($product instanceof WC_Product_Variation) {
            $this->removeFromVariation($product, $logger);

            return;
        }

        $this->removeFromProduct($product, $logger);
    }

    /**
     * @param WC_Product_Variation $variation
     * @param LoggerInterface $logger
     */
    private function removeFromVariation(
        WC_Product_Variation $variation,
        LoggerInterface $logger
    ): void {

        $attachmentId = (int) $variation->get_image_id();
        $remoteId = $this->imageIdMap->remoteId($attachmentId);

        $this->cleanupImage(self::VARIANT_TYPE, $attachmentId, $remoteId, $logger);
    }

    /**
     * @param WC_Product $product
     * @param LoggerInterface $logger
     */
    private function removeFromProduct(WC_Product $product, LoggerInterface $logger): void
    {
        $attachmentId = (int) $product->get_image_id();
        if ($attachmentId === null) {
            $logger->warning(
                sprintf(
                    'No attachment image ID found in %s %d',
                    get_class($product),
                    $product->get_id()
                )
            );

            return;
        }
        try {
            $remoteId = $this->imageIdMap->remoteId($attachmentId);
        } catch (IdNotFoundException $exception) {
            $logger->warning("Could not find Product Image {$attachmentId} for {$product->get_id()}");

            return;
        }

        $this->cleanupImage(self::PRODUCT_TYPE, $attachmentId, $remoteId, $logger);

        $this->cleanupVariants($product, $logger);
    }

    /**
     * @param WC_Product $product
     * @param LoggerInterface $logger
     */
    private function cleanupVariants(WC_Product $product, LoggerInterface $logger): void
    {
        $localVariantIds = $this->variantIds($product);

        if (empty($localVariantIds)) {
            $logger->error(
                "Cannot Unlink Variation Images, no Variations are found for product: {$product->get_id()}"
            );

            return;
        }

        foreach ($localVariantIds as $localVariantId) {
            $variation = $this->repository->findById($localVariantId);

            if ($variation === null) {
                continue;
            }

            if (!$variation->is_purchasable()) {
                continue;
            }

            $attachmentId = (int) $variation->get_image_id();

            try {
                $remoteId = $this->imageIdMap->remoteId($attachmentId);
            } catch (IdNotFoundException $exception) {
                $logger->error("Could not find Variant Image {$attachmentId} for {$localVariantId}");

                continue;
            }

            $this->cleanupImage(self::VARIANT_TYPE, $attachmentId, $remoteId, $logger);
        }
    }

    /**
     * @param string $type
     * @param int $attachmentId
     * @param string $remoteId
     * @param LoggerInterface $logger
     */
    private function cleanupImage(
        string $type,
        int $attachmentId,
        string $remoteId,
        LoggerInterface $logger
    ): void {

        try {
            $this->imageIdMap->deleteRecord($attachmentId, $remoteId);
        } catch (IdNotFoundException $exception) {
            $logger->info(
                // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
                "Could not delete {$type} image mapping {$attachmentId} <-> {$remoteId} - {$exception->getMessage()}"
            );
        }

        $logger->info("Deleted {$type} image mapping {$attachmentId} <-> {$remoteId}");
    }

    /**
     * @inheritDoc
     */
    public function isUnique(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::TYPE;
    }

    private function variantIds(WC_Product $product): array
    {
        if ($product instanceof WC_Product_Variable) {
            return (array) $product->get_visible_children();
        }

        return [$product->get_id()];
    }
}
