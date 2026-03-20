<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\AttributeSet;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\AttributeType as Attribute;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\UnexpectedBuilderPayloadTypeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Term;
class AttributeSetBuilder implements BuilderInterface
{
    use AttributeFormatterTrait;
    /**
     * @var ProductRepositoryInterface
     */
    private $repository;
    public function __construct(ProductRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    /**
     * @inheritDoc
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null): AttributeSet
    {
        if (!$payload instanceof WC_Product) {
            throw new UnexpectedBuilderPayloadTypeException(WC_Product::class, $payload);
        }
        $attributeSet = new AttributeSet();
        if ($payload instanceof WC_Product_Variation) {
            return $this->processVariation($payload, $attributeSet);
        }
        if ($payload instanceof WC_Product_Variable) {
            return $this->processVariable($payload, $attributeSet);
        }
        return $this->processSimple($payload, $attributeSet);
    }
    /**
     * @param WC_Product_Variable $wcProduct
     *
     * @param AttributeSet $attributeSet
     *
     * @return AttributeSet
     */
    public function processVariable(WC_Product_Variable $wcProduct, AttributeSet $attributeSet): AttributeSet
    {
        $variations = (array) $wcProduct->get_visible_children();
        if (empty($variations)) {
            return $attributeSet;
        }
        foreach ($variations as $variationId) {
            $variation = $this->repository->findById($variationId);
            if ($variation === null || !(bool) $variation->is_purchasable()) {
                continue;
            }
            assert($variation instanceof WC_Product_Variation);
            $attributeSet = $this->processVariation($variation, $attributeSet);
        }
        return $attributeSet;
    }
    /**
     * @param WC_Product_Variation $variation Variation to generate Attributes from
     * @param AttributeSet $attributeSet Current AttributeSet Instance -
     *                                              No Reference Operator, because of type-hint
     *
     * @return AttributeSet                         Return Updated AttributeSet
     *
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     * phpcs:disable Generic.Metrics.NestingLevel.MaxExceeded
     * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function processVariation(WC_Product_Variation $variation, AttributeSet $attributeSet): AttributeSet
    {
        $parentProduct = $this->repository->findByVariation($variation);
        if ($parentProduct === null) {
            return $attributeSet;
        }
        $variableAttributes = (array) $parentProduct->get_attributes();
        $variantAttributes = (array) $variation->get_attributes();
        foreach ($variantAttributes as $attributeName => $optionName) {
            /**
             * Ignore any variation attributes that do not exist on the parent.
             * Maybe unneeded, since WC should maintain this kind of integrity, but just to be sure
             */
            if (empty($variableAttributes[$attributeName])) {
                continue;
            }
            $label = wc_attribute_label($attributeName, $variation);
            /**
             * Add the 'Any' VariantOption for empty attribute options (which has the same meaning)
             */
            if (empty($optionName)) {
                $attributeSet->add($label, Attribute::ANY);
                continue;
            }
            /**
             * @var WC_Product_Attribute $attribute
             */
            $attribute = $variableAttributes[$attributeName];
            /**
             * In case of a taxonomy-based attribute, prefer the term name over the slug we already have
             */
            if ($attribute->is_taxonomy()) {
                $terms = $this->attributeTermsAssoc($attribute);
                if (!isset($terms[$optionName])) {
                    continue;
                    // needed?
                }
                $optionName = $terms[$optionName]->name;
            }
            /**
             * Prevent duplicates
             */
            if (in_array($optionName, $attributeSet->get($label), \true)) {
                continue;
            }
            $attributeSet->add($label, $optionName);
        }
        return $attributeSet;
    }
    /**
     * Store the WP_Term objects of a taxonomy attribute so that they can be accessed by key
     *
     * @param WC_Product_Attribute $attribute
     *
     * @return WP_Term[]
     */
    private function attributeTermsAssoc(WC_Product_Attribute $attribute): array
    {
        $terms = $attribute->get_terms() ?? [];
        $result = [];
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $result[$term->slug] = $term;
        }
        return $result;
    }
    /**
     * @param WC_Product $product
     *
     * @param AttributeSet $attributeSet
     *
     * @return AttributeSet
     */
    public function processSimple(WC_Product $product, AttributeSet $attributeSet): AttributeSet
    {
        $attributes = (array) $product->get_attributes();
        if (empty($attributes)) {
            return $attributeSet;
        }
        foreach ($attributes as $type => $attribute) {
            $wcAttribute = $attributes[$type];
            if (!$wcAttribute instanceof WC_Product_Attribute) {
                continue;
            }
            $options = $this->fromAttribute($attribute);
            if (empty($options)) {
                continue;
            }
            $attributeSet->add($this->attributeName($wcAttribute), implode(', ', $options));
        }
        return $attributeSet;
    }
}
