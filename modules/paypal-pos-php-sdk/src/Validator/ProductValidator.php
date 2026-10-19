<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\MaximumVariantsException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\MinimumVariantsException;
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
class ProductValidator implements ValidatorInterface
{
    public const MINIMUM_VARIANTS_AMOUNT = 0;
    public const MAXIMUM_VARIANTS_AMOUNT = 99;
    public function accepts(mixed $entity): bool
    {
        return $entity instanceof ProductInterface;
    }
    /**
     * @throws MaximumVariantsException
     *
     * @throws MinimumVariantsException
     */
    public function validate(mixed $product): bool
    {
        assert($product instanceof ProductInterface);
        $this->validateMinimumVariants($product);
        $this->validateMaximumVariants($product);
        return \true;
    }
    /**
     * @param ProductInterface $product
     *
     * @return bool
     *
     * @throws MinimumVariantsException
     *
     */
    public function validateMinimumVariants(ProductInterface $product): bool
    {
        if (count($product->variants()->all()) === self::MINIMUM_VARIANTS_AMOUNT) {
            throw new MinimumVariantsException(esc_html($product->name()));
        }
        return \true;
    }
    /**
     * @param ProductInterface $product
     *
     * @return bool
     *
     * @throws MaximumVariantsException
     *
     */
    public function validateMaximumVariants(ProductInterface $product): bool
    {
        $variantsAmount = count($product->variants()->all());
        if ($variantsAmount > self::MAXIMUM_VARIANTS_AMOUNT) {
            throw new MaximumVariantsException(esc_html($product->name()), (int) $variantsAmount);
        }
        return \true;
    }
}
