<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product;

use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;

/**
 * A Decorator for regular product instances. LazyProducts will automatically sync themselves
 * to Zettle as soon as their uuid() getter is called.
 */
class LazyProduct implements ProductTransferInterface
{
    use ProductGetterDecoratorTrait;
    use ProductSetterDecoratorTrait;

    /**
     * @var ProductTransferInterface
     */
    private $base;

    /**
     * @var string
     */
    private $syncedUuid;

    /**
     * @var Products
     */
    private $productClient;

    /**
     * @var OneToOneMapInterface|MapRecordCreator
     */
    private $map;

    /**
     * @var int
     */
    private $localId;

    public function __construct(
        int $localId,
        ProductTransferInterface $base,
        Products $productClient,
        OneToOneMapInterface $map
    ) {

        $this->localId = $localId;
        $this->base = $base;
        $this->productClient = $productClient;
        assert($map instanceof MapRecordCreator);
        $this->map = $map;
    }

    /**
     * @return string
     * @throws ZettleRestException
     */
    public function uuid(): string
    {
        if ($this->syncedUuid) {
            return $this->syncedUuid;
        }
        try {
            /**
             * Check if an ID-map entry has been added since the creation of this instance.
             * This might happen because of a concurrent web request.
             */
            $this->syncedUuid = $this->map->remoteId($this->localId());

            return $this->syncedUuid;
        } catch (IdNotFoundException $exception) {
        }

        $baseUuid = $this->base->uuid();
        /**
         * Try to read the product from Zettle first.
         * Maybe our mapping table just got out of sync
         *
         * TODO: Think again if this check makes any kind of sense
         * I did not want to remove it this close to initial release,
         * but it does not really make sense to me
         */
        try {
            $this->productClient->read($baseUuid);
            $this->syncedUuid = $this->base->uuid();
        } catch (ZettleRestException $exception) {
            /**
             * Alright, try to create the product on Zettle
             */
            $result = $this->productClient->create($this->base);
            $this->syncedUuid = $result->uuid();
        }
        $this->map->createRecord($this->localId, $this->syncedUuid);

        return $this->syncedUuid;
    }

    public function localId(): int
    {
        return $this->localId;
    }

    protected function baseProduct(): ProductInterface
    {
        return $this->base;
    }

    protected function baseWritableProduct(): WritableProductInterface
    {
        return $this->base;
    }
}
