<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use WC_Product_Attribute;
use WP_Taxonomy;
use WP_Term;

trait AttributeFormatterTrait
{
    /**
     * @param WC_Product_Attribute|string $attribute
     *
     * @return string
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected function attributeName($attribute): string
    {
        if ($attribute instanceof WC_Product_Attribute) {
            return $this->attributeNameFromAttribute($attribute);
        }

        return $this->formatAttributeName($attribute);
    }

    protected function formatAttributeName(string $name): string
    {
        if (substr($name, 0, 3) === 'pa_') {
            return substr($name, 3) ?: '';
        }

        return $name;
    }

    private function attributeNameFromAttribute(WC_Product_Attribute $attribute): string
    {
        if ($attribute->is_taxonomy()) {
            return $this->attributeNameFromTaxonomy(
                get_taxonomy($attribute->get_taxonomy())
            );
        }

        return $this->formatAttributeName((string) $attribute->get_name());
    }

    private function attributeNameFromTaxonomy(WP_Taxonomy $taxonomy): string
    {
        $needle = __('Product', 'woocommerce');
        $label = $taxonomy->label;

        if (strpos($label, $needle) === false) {
            return $this->formatAttributeName($label);
        }

        if (!isset($taxonomy->labels->singular_name)) {
            return str_replace($needle . ' ', '', $label);
        }

        return $this->formatAttributeName($taxonomy->labels->singular_name);
    }

    protected function nameFromTerm(WP_Term $term): string
    {
        return $this->formatAttributeName($term->slug);
    }

    protected function fromAttribute(WC_Product_Attribute $attribute): array
    {
        $options = $attribute->get_options();

        if (empty($options)) {
            return [];
        }

        if ($attribute->is_taxonomy()) {
            $options = array_map(
                function (int $termId): string {
                    return $this->nameFromTerm(get_term($termId));
                },
                $options
            );
        }

        return $options;
    }
}
