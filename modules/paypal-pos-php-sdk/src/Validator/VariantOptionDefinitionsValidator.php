<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
// phpcs:ignore Syde.Files.LineLength.TooLong
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions\EmptyVariantOptionCollectionException;
// phpcs:ignore Syde.Files.LineLength.TooLong
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions\EmptyVariantOptionDefinitionsException;
// phpcs:ignore Syde.Files.LineLength.TooLong
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions\MaximumVariantOptionDefinitionsAmountException;
class VariantOptionDefinitionsValidator implements ValidatorInterface
{
    public const MAXIMUM_DEFINITIONS_AMOUNT = 3;
    public function accepts(mixed $entity): bool
    {
        return $entity instanceof VariantOptionDefinitions;
    }
    public function validate(mixed $entity): bool
    {
        assert($entity instanceof VariantOptionDefinitions);
        if (empty($entity->definitions())) {
            throw new EmptyVariantOptionDefinitionsException();
        }
        $emptyVariantOptions = $this->validateVariantOptionDefinitions($entity);
        if (!empty($emptyVariantOptions)) {
            throw new EmptyVariantOptionCollectionException($emptyVariantOptions);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $amount = count($entity->definitions());
        /**
         * Count the VariantOptionDefinitions, because we need to verify this,
         * the Zettle API allows only a certain amount of different VariantOptionDefinitions
         *
         * Remove if this restriction got resolved by Zettle => IZET-285
         */
        if ($amount > self::MAXIMUM_DEFINITIONS_AMOUNT) {
            throw new MaximumVariantOptionDefinitionsAmountException(
                self::MAXIMUM_DEFINITIONS_AMOUNT,
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                (int) $amount
            );
        }
        return \true;
    }
    /**
     * @param VariantOptionDefinitions $definitions
     *
     * @return string[]
     */
    private function validateVariantOptionDefinitions(VariantOptionDefinitions $definitions): array
    {
        $emptyProperties = [];
        foreach ($definitions->definitions() as $name => $properties) {
            $props = $properties->all();
            if (!empty($props)) {
                continue;
            }
            $emptyProperties[] = $name;
        }
        return $emptyProperties;
    }
}
