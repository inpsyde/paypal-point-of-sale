<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\DifferentVariantVatException;
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
class VariableProductVatValidator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function accepts($entity): bool
    {
        return $entity instanceof ProductInterface && !empty($entity->variants()->all());
    }
    /**
     * @param ProductInterface $product
     *
     * @return bool
     * @throws DifferentVariantVatException
     */
    public function validate($product): bool
    {
        assert($product instanceof ProductInterface);
        $this->checkVat($product);
        return \true;
    }
    private function checkVat(ProductInterface $product): void
    {
        $vats = array_merge([$product->vat()], array_map(static function (VariantInterface $variant): ?Vat {
            return $variant->vat();
        }, $product->variants()->all()));
        $uniqueVats = array_unique(array_map(static function (?Vat $vat): ?float {
            return $vat ? $vat->percentage() : null;
        }, $vats));
        if (count($uniqueVats) > 1) {
            throw new DifferentVariantVatException(esc_html($product->name()), $uniqueVats);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
    }
}
