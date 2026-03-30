<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\StockQuantityAwareInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\MaximumStockException;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint

class StockValidator implements ValidatorInterface
{
    protected int $maxStock;

    /**
     * @param int $maxStock
     */
    public function __construct(int $maxStock)
    {
        $this->maxStock = $maxStock;
    }

    /**
     * @inheritDoc
     */
    public function accepts($entity): bool
    {
        return $entity instanceof StockQuantityAwareInterface;
    }

    /**
     * @inheritDoc
     */
    public function validate($entity): bool
    {
        assert($entity instanceof StockQuantityAwareInterface);

        $stock = $entity->defaultQuantity();

        if ($stock > $this->maxStock) {
            throw new MaximumStockException($stock, $this->maxStock);
        }

        return true;
    }
}
