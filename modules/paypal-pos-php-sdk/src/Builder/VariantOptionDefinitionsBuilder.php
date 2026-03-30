<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\AttributeSet;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection as Collection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions as Definitions;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\UnexpectedBuilderPayloadTypeException;
use WC_Product;
use WC_Product_Variable;
class VariantOptionDefinitionsBuilder implements BuilderInterface
{
    /**
     * @inheritDoc
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null): Definitions
    {
        if (!$payload instanceof WC_Product) {
            throw new UnexpectedBuilderPayloadTypeException(WC_Product::class, $payload);
        }
        $definitions = new Definitions();
        if (!$payload->is_type(ProductType::VARIABLE)) {
            return $definitions;
        }
        assert($payload instanceof WC_Product_Variable);
        if (!$builder) {
            return $definitions;
        }
        return $this->setToDefinitions($builder->build(AttributeSet::class, $payload));
    }
    /**
     * @param AttributeSet $set
     *
     * @return Definitions
     */
    private function setToDefinitions(AttributeSet $set): Definitions
    {
        $definitions = new Definitions();
        if (empty($set->all())) {
            return $definitions;
        }
        foreach ($set->all() as $type => $attributes) {
            $collection = new Collection();
            foreach ($attributes as $attribute) {
                $collection->add(new VariantOption($type, $attribute));
            }
            $definitions->addCollectionToDefinition($type, $collection);
        }
        return $definitions;
    }
}
