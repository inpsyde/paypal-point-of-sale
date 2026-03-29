<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Job;

use Inpsyde\Queue\Exception\QueueLockedException;
use Inpsyde\Queue\Exception\QueueRuntimeException;
use Inpsyde\Queue\ExceptionLoggingTrait;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\LazyProduct;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductTransferInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\RemoteIdProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\PayPal\PointOfSale\Sync\VariableInventoryChecker;
use Throwable;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * This Job is a central piece of infrastructure that synchronizes the current state of a WC_Product
 * over to Zettle. It fetches the WC_Product from the database and passes it into the Builder.
 * The result is then either updated or created via REST api
 * depending on whether or not the Product already exists.
 *
 * For newly created products, it will also dispatch a follow-up job to sync the product inventory
 * if needed
 */
class ExportProductJob implements Job
{
    use ExceptionLoggingTrait;
    use VariableInventoryChecker;

    const TYPE = 'sync-product';

    private ProductRepositoryInterface $repository;

    private BuilderInterface $builder;

    private Products $productsClient;

    /**
     * We are using a custom QueueProcessor for executing follow-up jobs directly
     * instead of adding them to the repository that we get passed in execute()
     *
     * Doing that makes the number of sync-jobs completely predictable: 1 product -> 1 job
     * -which in turn helps us determine how far we've progressed in a larger sync process.
     *
     * This instance should therefore run on a separate JobRepository
     *
     */
    private QueueProcessor $processor;

    /**
     * @var callable
     */
    private $createJobRecord;

    private RemoteIdProvider $remoteIdProvider;

    /**
     * @var callable(int):bool
     */
    private $isSyncable;

    /**
     * @var callable
     */
    private $productStatus;

    /**
     * UpdateProductJob constructor.
     *
     * @param ProductRepositoryInterface $repository
     * @param BuilderInterface $builder
     * @param Products $productsClient
     * @param RemoteIdProvider $remoteIdProvider
     * @param QueueProcessor $processor
     * @param callable $createJobRecord
     * @param callable(int):bool $isSyncable
     * @param callable(int):bool $productStatus
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        BuilderInterface $builder,
        Products $productsClient,
        RemoteIdProvider $remoteIdProvider,
        QueueProcessor $processor,
        callable $createJobRecord,
        callable $isSyncable,
        callable $productStatus
    ) {

        $this->repository = $repository;
        $this->builder = $builder;
        $this->productsClient = $productsClient;
        $this->remoteIdProvider = $remoteIdProvider;
        $this->processor = $processor;
        $this->createJobRecord = $createJobRecord;
        $this->isSyncable = $isSyncable;
        $this->productStatus = $productStatus;
    }

    /**
     * @inheritDoc
     *
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {
        /**
         * Reuse the logger we got passed in any follow-up jobs
         */
        if ($this->processor instanceof LoggerAwareInterface) {
            $this->processor->setLogger($logger);
        }
        $productId = (int) $context->args()->productId;
        $wcProduct = $this->repository->findById($productId);

        if ($wcProduct === null) {
            $logger->warning("Could not find WC Product with ID {$productId}");

            return true;
        }

        if ($wcProduct instanceof WC_Product_Variation) {
            $wcProduct = $this->repository->findByVariation($wcProduct);

            if ($wcProduct === null) {
                $logger->warning(
                    sprintf("Could not find parent from Variation %s", $productId)
                );

                return true;
            }
        }

        // in some cases we may have the cached product instances,
        // so requesting to clear these caches to make sure that we are not sending incorrect/outdated data,
        // e.g. when building a product too early during new product publishing, it may not have the price.
        do_action('paypal-pos.clear-product-cache');

        if (!($this->isSyncable)($productId)) {
            $productStatus = ($this->productStatus)($productId);
            $logger->warning(
                sprintf(
                    'Skipping %s %s, not syncable because: %s.',
                    get_class($wcProduct),
                    $productId,
                    implode(', ', $productStatus)
                )
            );

            return true;
        }

        try {
            $product = $this->builder->build(ProductInterface::class, $wcProduct);
        } catch (BuilderException $exception) {
            $logger->error(
                sprintf(
                    "Error while building PayPal Point of Sale Product from WooCommerce product %d. Skipping",
                    $productId
                )
            );
            $this->logException($exception, $logger);
            $this->removeProductIfExists($productId, $logger);

            return true;
        }

        $this->syncProduct(
            $product,
            $productId,
            $logger,
            function () use ($wcProduct) {
                $this->afterCreate($wcProduct);
            }
        );

        /**
         * Process any follow-up jobs that might have been added
         */
        try {
            $this->processor->process();
        } catch (QueueLockedException $exception) {
            $logger->error('The sub-queue of this %s job was locked. This is very bad');
        }

        return true;
    }

    /**
     * @param ProductTransferInterface $product
     * @param int $localProductId
     * @param LoggerInterface $logger
     * @param callable|null $onCreate
     *
     * @throws QueueRuntimeException
     */
    private function syncProduct(
        ProductTransferInterface $product,
        int $localProductId,
        LoggerInterface $logger,
        ?callable $onCreate = null
    ): void {
        /**
         * LazyProducts have no entry in our ID map, but can 'auto-sync' by touching their uuid.
         */
        if ($product instanceof LazyProduct) {
            $this->attemptCreate($product, $logger, $onCreate);
            return;
        }

        /**
         * Regular products have an entry in the ID map, so we will attempt to update normally.
         */
        $this->attemptUpdate($product, $localProductId, $logger);
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

    /**
     * Sends off an update request via the REST client. If this fails because
     * the product could not be found, it will attempt to create it instead
     *
     * @param ProductTransferInterface $product
     * @param int $localProductId
     * @param LoggerInterface $logger
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    private function attemptUpdate(
        ProductTransferInterface $product,
        int $localProductId,
        LoggerInterface $logger
    ): void {

        try {
            try {
                $this->productsClient->update($product);
            } catch (ZettleRestException $exception) {
                /**
                 * Regularly attempt to create the product using the REST client if
                 * the product does not exist remotely.
                 *
                 * This should only happen if the product was deleted from Zettle and the 'ProductDelete'
                 * webhook failed, because we should not have a concrete Product with existing UUID
                 * otherwise.
                 */
                if ($exception->isType(ZettleRestException::TYPE_ENTITY_NOT_FOUND)) {
                    $msg = sprintf(
                        "Could not find remote product %s despite local record being present. "
                        . "This product appears to have been removed manually. Attempting to re-create it...",
                        $product->uuid()
                    );

                    $logger->info($msg);

                    $this->processor->repository()->add(
                        ($this->createJobRecord)(
                            UnlinkProductJob::TYPE,
                            [
                                'localId' => $localProductId,
                            ]
                        )
                    );

                    $this->processor->process();

                    throw new QueueRuntimeException($msg); // will cause this job to retry
                }
                throw $exception;
            }
        } catch (QueueRuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            /**
             * The current product cannot be transformed into a valid Zettle product.
             * We log this error, but to not signal it upwards, essentially skipping the product
             */
            $this->logException($exception, $logger);
            $logger->warning(
                sprintf(
                    "Error while updating Product %s. Skipping...",
                    $product->uuid()
                )
            );
        }
    }

    /**
     * When a product has been re/created at Zettle, we should ensure its stock is in sync as well
     * (which is a second step in iZ land).
     * Enqueues a SyncStockJob (which will automatically enable inventory tracking if needed)
     *
     * @param WC_Product $product
     *
     * @see SyncStockJob
     *
     */
    private function afterCreate(
        WC_Product $product
    ): void {

        if ($product instanceof WC_Product_Simple && !(bool) $product->managing_stock()) {
            return;
        }

        if (
            $product instanceof WC_Product_Variable
            && (!(bool) $product->managing_stock() && !$this->hasStockManagingVariations($product))
        ) {
            return;
        }
        /**
         * While SyncStockJob can enable inventory tracking on its own,
         * it will only do so as a recovery of a failed sync call.
         * So for performance reasons, we manually enable tracking
         * first since we know it has to be done anyway.
         */
        $this->processor->repository()->add(
            ($this->createJobRecord)(
                SetInventoryTrackingJob::TYPE,
                [
                    'productId' => $product->get_id(),
                    'state' => true,
                ]
            )
        );

        $this->processor->repository()->add(
            ($this->createJobRecord)(
                SyncStockJob::TYPE,
                [
                    'productId' => $product->get_id(),
                    'frontOffice' => false,
                ]
            )
        );
    }

    /**
     * @param LazyProduct $product
     * @param LoggerInterface $logger
     * @param callable|null $onSuccess
     *
     * @throws QueueRuntimeException
     */
    private function attemptCreate(
        LazyProduct $product,
        LoggerInterface $logger,
        ?callable $onSuccess = null
    ): void {

        try {
            /**
             * Products that are not yet connected
             * will auto-sync as soon as you access their UUID.
             * Therefore we simply "touch" the uuid() after creating the Product instance
             */
            $product->uuid();

            // Log if the Product was created
            $logger->info(
                sprintf(
                    'Product with Id:%d and Uuid:%s was successfully created at PayPal Point of Sale Backoffice.',
                    $product->localId(),
                    $product->uuid()
                )
            );
            $onSuccess and $onSuccess($product);
        } catch (ZettleRestException $exception) {
            throw new QueueRuntimeException(
                sprintf(
                    "Couldn't update the Product %d - API returned with Code %d and message %s",
                    $product->localId(),
                    $exception->getCode(),
                    $exception->getMessage()
                ),
                500,
                $exception
            );
        }
    }

    /**
     * This method is run when we had a validation error with the current state of the WC_Product.
     * It can no longer be synced to iZ, so any further changes will never arrive there.
     * Thus, deleting it remotely is our only real course of action to prevent getting data out of sync.
     *
     * @param int $productId
     * @param LoggerInterface $logger
     */
    private function removeProductIfExists(
        int $productId,
        LoggerInterface $logger
    ): void {

        try {
            $remoteId = $this->remoteIdProvider->remoteId($productId);
        } catch (IdNotFoundException $exception) {
            $logger->info('No id-map cleanup needed');

            return;
        }
        $logger->info(
            sprintf(
                'Dispatching cleanup job for remote product %s and its mapping to local product %d',
                $remoteId,
                $productId
            )
        );
        $this->processor->repository()->add(
            ($this->createJobRecord)(
                DeleteProductJob::TYPE,
                [
                    'productId' => $productId,
                ]
            )
        );
    }
}
