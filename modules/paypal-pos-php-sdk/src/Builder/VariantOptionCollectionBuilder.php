<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\AttributeSet;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection as Collection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\UnexpectedBuilderPayloadTypeException;
use WC_Product;
class VariantOptionCollectionBuilder implements BuilderInterface
{
    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): Collection
    {
        if (!$payload instanceof WC_Product) {
            throw new UnexpectedBuilderPayloadTypeException(WC_Product::class, $payload);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $collection = new Collection();
        if ($payload->is_type(ProductType::SIMPLE)) {
            return $collection;
        }
        if (!$builder) {
            return $collection;
        }
        return $this->setToCollection($builder->build(AttributeSet::class, $payload), $builder);
    }
    private function setToCollection(AttributeSet $set, BuilderInterface $builder): Collection
    {
        $collection = new Collection();
        if (empty($set->all())) {
            return $collection;
        }
        foreach ($set->all() as $type => $attributes) {
            foreach ($attributes as $attribute) {
                $collection->add($builder->build(VariantOption::class, ['name' => $type, 'value' => $attribute]));
            }
        }
        return $collection;
    }
}
