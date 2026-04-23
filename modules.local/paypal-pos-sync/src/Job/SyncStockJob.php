<?php

declare(strict_types=1);

// TODO: Class needs to be refactored because of nesting level and complexity of reading
// phpcs:disable Generic.Metrics.NestingLevel.TooHigh

namespace Syde\PayPal\PointOfSale\Sync\Job;

use Inpsyde\Queue\ExceptionLoggingTrait;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\WcProductContracts\ProductType;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Inventory\Inventory;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory\Inventory as InventoryEntity;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Throwable;
use WC_Product;
use WC_Product_Variation;

class SyncStockJob implements Job
{
    use ExceptionLoggingTrait;

    public const TYPE = 'sync-product-stock';

    private Inventory $inventoryClient;

    private BuilderInterface $builder;

    private OneToManyMapInterface $map;

    private int $maxStockChange;

    private Job $setInventoryTrackingJob;

    /**
     * @param int $maxStockChange The API does not allow transactions larger than 100.000
     */
    public function __construct(
        Inventory $inventoryClient,
        BuilderInterface $builder,
        OneToManyMapInterface $map,
        int $maxStockChange,
        Job $setInventoryTrackingJob
    ) {

        $this->inventoryClient = $inventoryClient;
        $this->builder = $builder;
        $this->map = $map;
        $this->maxStockChange = $maxStockChange;
        $this->setInventoryTrackingJob = $setInventoryTrackingJob;
    }

    /**
     * @inheritDoc
     *
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        $productId = $context->args()->productId;
        $wcProduct = wc_get_product($productId);

        if ($wcProduct === false) {
            $logger->error("Cant find Product with ProductID: {$productId}");

            return false;
        }
        $isFrontOffice = (bool) $context->args()->frontOffice;

        try {
            $productUuid = $this->getProductUuid($wcProduct);
            $wcProductClass = get_class($wcProduct);

            $logger->info(
                sprintf(
                    'Attempting to sync inventory of %s %s with PayPal Point of Sale UUID %s',
                    $wcProductClass,
                    $productId,
                    $productUuid
                )
            );
            $inventory = $this->inventoryClient->productInventory($productUuid, 'STORE');
        } catch (BuilderException | ZettleRestException $exception) {
            if ($this->isRecoverable($exception)) {
                $this->dispatchSetInventoryTrackingJob($wcProduct, $logger, $context);

                return false;
            }
            $this->logException($exception, $logger);

            return false;
        }

        // Clear cache, otherwise we can get outdated WC stock numbers (if the product was accessed earlier during this request and updated in another request).
        if (apply_filters('paypal-point-of-sale.sync.clear-cache-before-stock-sync', true)) {
            wp_cache_flush_runtime();
        }

        foreach (
            $this->fetchVariantUuids(
                $wcProduct,
                $logger
            ) as $variantUuid => $localId
        ) {
            $wcProduct = wc_get_product($localId);
            $newStock = (int) $wcProduct->get_stock_quantity();

            $remoteStock = $this->fetchRemoteStock(
                $inventory,
                $variantUuid
            );
            $stockChange = $newStock - $remoteStock;

            if ($stockChange === 0) {
                $logger->debug("Skipping product $localId: $newStock - $remoteStock.");
                continue; // already synced. Can happen if e.g. merging products in onboarding
            }

            $cappedStockChange = $this->cappedStockChange($stockChange);

            /**
             * Now with the stock quantities ensured to be balanced,
             * we can sync our actual stock transaction
             */
            $transaction = $isFrontOffice
                ? $this->frontendTransaction($cappedStockChange)
                : $this->backendTransaction($cappedStockChange);

            $result = $this->syncVariant(
                $productUuid,
                $variantUuid,
                $transaction,
                $logger
            );
            if (!$result) {
                return false;
            }
            /**
             * Take care of the remaining stock change
             * by adding a new sync job just for this product
             */
            if ($cappedStockChange !== $stockChange) {
                $logger->info(
                    "This stock change $stockChange is larger than allowed by PayPal Point of Sale."
                    . ' We will sync the rest in a new request'
                );
                $this->execute(
                    Context::fromArray(
                        [
                            'productId' => $localId,
                        ]
                    ),
                    $repository,
                    $logger
                );
            }
        }

        return true;
    }

    /**
     * Dispatches job to enable inventory tracking for the given WC_Product
     * If we are dealing with a WC_Product_Variation, we will use the parent
     * because there is no variant-level inventory tracking at iZ
     *
     * @param WC_Product $wcProduct
     * @param LoggerInterface $logger
     * @param ContextInterface $context
     */
    private function dispatchSetInventoryTrackingJob(
        WC_Product $wcProduct,
        LoggerInterface $logger,
        ContextInterface $context
    ): void {

        $productId = (int) $wcProduct->get_id();
        if ($wcProduct instanceof WC_Product_Variation) {
            $productId = (int) $wcProduct->get_parent_id();
        }
        $logger->notice('Inventory tracking needs to be enabled before stock can be synced');

        $this->setInventoryTrackingJob->execute(
            Context::fromArray(
                [
                    'productId' => $productId,
                    'state' => true,
                ]
            ),
            new EphemeralJobRepository(),
            $logger
        );

        $logger->notice(
            sprintf(
                '%s has finished self-healing',
                $this->type()
            )
        );
    }

    /**
     * Filter the Stock Change to iterate in the Zettle API limitations
     *
     * @param int $stockChange
     *
     * @return int
     */
    private function cappedStockChange(int $stockChange): int
    {
        // More than 100000 limit it to 100000
        if ($stockChange > $this->maxStockChange) {
            return $this->maxStockChange;
        }

        // Less than -100000 limit it to -100000
        if ($stockChange < -$this->maxStockChange) {
            return -$this->maxStockChange;
        }

        return $stockChange;
    }

    /**
     * @param WC_Product $wcProduct
     *
     * @return string
     * @throws BuilderException
     */
    private function getProductUuid(WC_Product $wcProduct): string
    {
        if ($wcProduct->is_type(ProductType::VARIATION)) {
            $wcProduct = wc_get_product($wcProduct->get_parent_id());
        }

        $product = $this->builder->build(ProductInterface::class, $wcProduct);
        assert($product instanceof ProductInterface);

        return (string) $product->uuid();
    }

    /**
     * Grab the current Zettle stock from the Inventory API. Return 0
     * if we don't know about the remote product yet.
     *
     * @param InventoryEntity $inventory
     * @param string $variantUuid
     *
     * @return int
     */
    private function fetchRemoteStock(
        InventoryEntity $inventory,
        string $variantUuid
    ): int {

        try {
            return $inventory->variantBalance($variantUuid);
        } catch (IdNotFoundException $exception) {
            return 0;
        }
    }

    private function syncVariant(
        string $productUuid,
        string $variantUuid,
        array $transaction,
        LoggerInterface $logger
    ): bool {

        try {
            $this->inventoryClient->moveStock(
                $productUuid,
                $variantUuid,
                $transaction['from'],
                $transaction['to'],
                $transaction['change']
            );

            $logger->info(
                sprintf(
                    "Moved %s items from %s to %s",
                    $transaction['change'],
                    $transaction['from'],
                    $transaction['to']
                ),
                [
                    'productUuid' => $productUuid,
                    'variantUuid' => $variantUuid,
                ]
            );
        } catch (ZettleRestException $exception) {
            $this->logException($exception, $logger);

            /**
             * Returning false means the job can be retried.
             * It could be that 'manage_stock' has only recently been enabled and we
             * are still waiting for a SetInventoryTrackingJob
             */
            return !$this->isRecoverable($exception);
        }

        return true;
    }

    private function isRecoverable(Throwable $exception): bool
    {
        if (!$exception instanceof ZettleRestException) {
            return false;
        }

        if ($exception->isType(ZettleRestException::TYPE_PRODUCT_NOT_TRACKED)) {
            return true;
        }

        return false;
    }

    private function frontendTransaction(int $change): array
    {
        if ($change <= 0) {
            return [
                'from' => 'STORE',
                'to' => 'SOLD',
                'change' => abs($change),
            ];
        }

        // This could be a refund
        return [
            'from' => 'SOLD',
            'to' => 'STORE',
            'change' => abs($change),
        ];
    }

    private function backendTransaction(int $change): array
    {
        if ($change <= 0) {
            return [
                'from' => 'STORE',
                'to' => 'BIN',
                'change' => abs($change),
            ];
        }

        return [
            'from' => 'SUPPLIER',
            'to' => 'STORE',
            'change' => abs($change),
        ];
    }

    /**
     * @param WC_Product $product
     * @param LoggerInterface $logger
     *
     * @return array<string, int>
     *
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
     */
    private function fetchVariantUuids(
        WC_Product $product,
        LoggerInterface $logger
    ): array {

        $parentProduct = $product;
        if ($product->is_type(ProductType::VARIATION)) {
            $parentProduct = wc_get_product($product->get_parent_id());

            if (!$parentProduct) {
                $logger->warning(
                    sprintf(
                        'Could not find parent product %s for %s',
                        $product->get_parent_id(),
                        $product->get_id()
                    )
                );

                return [];
            }
        }
        try {
            $collection = $this->builder->build(VariantCollection::class, $parentProduct);
        } catch (BuilderException $exception) {
            $this->logException($exception, $logger);

            return [];
        }

        /**
         * Since we need to ensure that all variants already exist
         * on Zettle in order to sync stock for them, we ensure that by touching each UUID
         * which will trigger record creation
         */
        foreach ($collection->all() as $variant) {
            $variant->uuid();
        }
        $result = [];

        if ($product->is_type(ProductType::VARIATION)) {
            $variantUuids = [];
            try {
                $variantUuids = $this->map->remoteIds((int) $product->get_id());
            } catch (IdNotFoundException $exception) {
                $this->logException($exception, $logger);
            }

            foreach ($collection->all() as $variant) {
                if (in_array((string) $variant->uuid(), $variantUuids, true)) {
                    $result[(string) $variant->uuid()] = $product->get_id();
                }
            }
            return $result;
        }
        if ($product->is_type(ProductType::VARIABLE)) {
            foreach ($collection->all() as $variant) {
                /**
                 * Weed out variants/variations that manage their stock themselves
                 */
                try {
                    $variationId = $this->map->localId((string) $variant->uuid());
                } catch (IdNotFoundException $exception) {
                    $this->logException($exception, $logger);
                    continue;
                }

                $wcVariationProduct = wc_get_product($variationId);

                if (!$wcVariationProduct) {
                    continue;
                }
                $result[(string) $variant->uuid()] = $variationId;
            }
            return $result;
        }
        foreach ($collection->all() as $variant) {
            $result[(string) $variant->uuid()] = $product->get_id();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::TYPE;
    }
}
