<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;

class UnlinkVariantJob implements Job
{
    const TYPE = 'unlink-variant';

    private OneToOneMapInterface|MapRecordCreator $variantMap;

    /**
     * VariantRepository constructor.
     *
     * @param OneToOneMapInterface|MapRecordCreator $variantMap
     */
    public function __construct(
        OneToOneMapInterface $variantMap
    ) {

        if (!$variantMap instanceof MapRecordCreator) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected ID-Map of type %s to implement %s.',
                    get_class($variantMap),
                    MapRecordCreator::class
                )
            );
        }
        $this->variantMap = $variantMap;
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
        // Remove with VariationId
        if (isset($context->args()->variationId)) {
            return $this->removeWithVariationId((int) $context->args()->variationId, $logger);
        }

        // Remove with VariantUuid
        if (isset($context->args()->variantUuid)) {
            return $this->removeWithVariantUuid((string) $context->args()->variantUuid, $logger);
        }

        $logger->error("Can't unlink Variant - no valid variationId or variantUuid provided.");

        return true;
    }

    /**
     * @param int $variationId
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    private function removeWithVariationId(int $variationId, LoggerInterface $logger): bool
    {
        try {
            $this->deleteVariantRecord($variationId, $this->variantMap->remoteId($variationId));
            $logger->info(sprintf('Deleted id-map entry for variant %s', $variationId));

            return true;
        } catch (IdNotFoundException $exception) {
            $logger->warning(sprintf('Could not delete id-map entry for variant %s', $variationId));

            return true; // No reason to retry this job even in case of failure
        }
    }

    /**
     * @param string $variantUuid
     * @param LoggerInterface $logger
     *
     * @return bool
     */
    private function removeWithVariantUuid(string $variantUuid, LoggerInterface $logger): bool
    {
        try {
            $this->deleteVariantRecord($this->variantMap->localId($variantUuid), $variantUuid);
            $logger->info(sprintf('Deleted id-map entry for remote variant %s', $variantUuid));

            return true;
        } catch (IdNotFoundException $exception) {
            $logger->warning(sprintf('Could not delete id-map entry for remote variant %s', $variantUuid));

            return true; // No reason to retry this job even in case of a failure above
        }
    }

    /**
     * @param int $variantId
     * @param string $uuid
     *
     * @return bool
     * @throws IdNotFoundException
     */
    private function deleteVariantRecord(int $variantId, string $uuid): bool
    {
        return $this->variantMap->deleteRecord($variantId, $uuid);
    }
}
