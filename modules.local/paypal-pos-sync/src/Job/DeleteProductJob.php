<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Job;

use Inpsyde\Queue\ExceptionLoggingTrait;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\RemoteIdProvider;
use Psr\Log\LoggerInterface;

class DeleteProductJob implements Job
{
    use ExceptionLoggingTrait;

    const TYPE = 'delete-product';

    /**
     * @var Products
     */
    private $productsClient;

    /**
     * @var RemoteIdProvider
     */
    private $remoteIdProvider;

    /**
     * @var callable
     */
    private $createJobRecord;

    /**
     * DeleteProductJob constructor.
     *
     * @param Products $productsClient
     */
    public function __construct(
        RemoteIdProvider $remoteIdProvider,
        Products $productsClient,
        callable $createJobRecord
    ) {

        $this->remoteIdProvider = $remoteIdProvider;
        $this->productsClient = $productsClient;
        $this->createJobRecord = $createJobRecord;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        $args = $context->args();
        $productId = (int) $args->productId;
        $remoteOnly = property_exists($args, 'remoteOnly')
            ? (bool) $args->remoteOnly
            : false;

        if (!$productId) {
            $logger->warning("Cannot delete product: Invalid local ID");

            return true;
        }

        try {
            $uuid = $this->remoteIdProvider->remoteId($productId);
        } catch (IdNotFoundException $exception) {
            $logger->info("Remote UUID of product with ID {$productId} not found");

            return true;
        }

        try {
            $this->productsClient->delete($uuid, !$remoteOnly);
        } catch (ZettleRestException $exception) {
            $this->logException($exception, $logger);

            return false;
        }
        if (!$remoteOnly) {
            /**
             * Enqueue a job that unlinks the id-map entry for this product.
             * This is technically redundant since we expect the REST client to trigger
             * the appropriate listeners to perform this sort of cleanup but better safe than sorry
             */
            $repository->add(
                ($this->createJobRecord)(
                    UnlinkProductJob::TYPE,
                    [
                        'localId' => $productId,
                    ]
                )
            );
        }

        $logger->info(
            sprintf(
                "The Product %s|%s was successfully deleted from PayPal Point of Sale Backoffice.",
                $productId,
                $uuid
            )
        );

        return true;
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
}
