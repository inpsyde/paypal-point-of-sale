<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\StockQuantityAwareInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\MaximumStockException;
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
    public function accepts(mixed $entity): bool
    {
        return $entity instanceof StockQuantityAwareInterface;
    }
    public function validate(mixed $entity): bool
    {
        assert($entity instanceof StockQuantityAwareInterface);
        $stock = $entity->defaultQuantity();
        if ($stock > $this->maxStock) {
            throw new MaximumStockException((int) $stock, (int) $this->maxStock);
        }
        return \true;
    }
}
