<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;

class LazyVariant implements VariantInterface, StockQuantityAwareInterface, PriceAwareInterface
{
    use VariantGetterDecoratorTrait;

    private int $localId;

    private VariantTransferInterface $base;

    private MapRecordCreator $recordCreator;

    private bool $persisted = false;

    public function __construct(
        int $localId,
        VariantTransferInterface $base,
        MapRecordCreator $recordCreator
    ) {

        $this->localId = $localId;
        $this->base = $base;
        $this->recordCreator = $recordCreator;
    }

    public function uuid(): string
    {
        if ($this->persisted) {
            return $this->base->uuid();
        }
        try {
            /**
             * Check idMap for an existing record first.
             * A record might have been already created by a separate identical
             * instance of LazyVariant
             */
            $remoteId = $this->recordCreator->remoteId($this->localId);
            $this->base->setUuid($remoteId);
        } catch (IdNotFoundException $exception) {
            $this->recordCreator->createRecord($this->localId, $this->base->uuid());
        }

        $this->persisted = true;

        return $this->base->uuid();
    }

    public function defaultQuantity(): int
    {
        return $this->base->defaultQuantity();
    }

    public function setDefaultQuantity(int $defaultQuantity): void
    {
        $this->base->setDefaultQuantity($defaultQuantity);
    }

    public function setPrice(?Price $price): void
    {
        $this->base->setPrice($price);
    }

    protected function baseVariant(): VariantInterface
    {
        return $this->base;
    }
}
