<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint

namespace Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
// phpcs:ignore Syde.Files.LineLength.TooLong
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions\VariantOptionAmountMismatchException;

/**
 * The REST API will complain in Variants contain VariantOption,
 * but the Product itself does not contain matching VariantOptionDefinitions
 *
 * It could be that this Validator would need to be expanded so that it
 * actually checks if both of them contain matching data, but we currently get by
 * with checking if the number of entries matches
 */
class ProductVariantOptionDefinitionsValidator implements ValidatorInterface
{
    public function accepts(mixed $entity): bool
    {
        return $entity instanceof ProductInterface;
    }

    public function validate(mixed $entity): bool
    {
        assert($entity instanceof ProductInterface);

        $options = [];
        $variantOptionDefinitions = $entity->variantOptionDefinitions();

        foreach ($entity->variants()->all() as $variant) {
            $variantOptions = $variant->options();
            $currentOptions = $variantOptions !== null ? $variantOptions->all() : [];

            foreach ($currentOptions as $currentOption) {
                $options[$currentOption->name()][] = $currentOption->value();
            }
        }

        if (empty($options) && $variantOptionDefinitions === null) {
            return true;
        }

        if (!empty($options) && $variantOptionDefinitions === null) {
            return false;
        }

        $this->assertVariantOptionAmounts($entity);

        return true;
    }

    /**
     * @param ProductInterface $product
     *
     * @throws VariantOptionAmountMismatchException
     */
    private function assertVariantOptionAmounts(ProductInterface $product): void
    {
        $variantOptionDefinitions = $product->variantOptionDefinitions();
        $definitions = $variantOptionDefinitions !== null ? $variantOptionDefinitions->definitions() : [];

        foreach ($product->variants()->all() as $variant) {
            $variantOptions = $variant->options();
            $options = $variantOptions !== null ? $variantOptions->all() : [];
            $definitionsAmount = count($definitions);
            $currentOptionsAmount = count($options);

            if ($definitionsAmount !== $currentOptionsAmount) {
                throw new VariantOptionAmountMismatchException(
                    (int) $definitionsAmount,
                    (int) $currentOptionsAmount
                );
            }
        }
    }
}
