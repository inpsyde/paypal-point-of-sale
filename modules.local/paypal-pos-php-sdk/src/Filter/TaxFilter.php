<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\LazyProduct;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\WritableProductInterface;

/**
 * Sets taxExempt and usesDefaultTax to null for existing items
 * because we are not supposed to overwrite taxes after creation.
 * For VAT accounts sets taxExempt to false (IZET-374).
 */
class TaxFilter implements FilterInterface
{
    /**
     * @var callable():string
     */
    protected $taxationType;

    /**
     * @param callable():string $taxationType Returns one of TaxationType values.
     * It's callback because of the recursive dependency.
     */
    public function __construct(callable $taxationType)
    {
        $this->taxationType = $taxationType;
    }

    /**
     * @inheritDoc
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function accepts($entity, $payload): bool
    {
        // phpcs:enable
        return $entity instanceof WritableProductInterface && $this->existsRemotely($entity);
    }

    /**
     * @inheritDoc
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     */
    public function filter($entity, $wcProduct)
    {
        // phpcs:enable

        assert($entity instanceof WritableProductInterface);

        $entity->setTaxExempt(($this->taxationType)() === TaxationType::SALES_TAX ? null : false);
        $entity->setUsesDefaultTax(null);

        return $entity;
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
    private function existsRemotely($entity): bool
    {
        // phpcs:enable
        return !($entity instanceof LazyProduct);
    }
}
