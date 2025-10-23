<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider;

use Faker\Provider\Text;

class VariantOptionSampleData
{

    public static function variantOption()
    {
        yield [self::sampleVariantOption()];
    }

    public static function variantOptions()
    {
        $firstRandomVariantOptionType = self::randomVariantOptionType();
        $secondRandomVariantOptionType = self::randomVariantOptionType($firstRandomVariantOptionType);

        yield [
            [
                self::sampleVariantOption($firstRandomVariantOptionType),
                self::sampleVariantOption($secondRandomVariantOptionType)
            ]
        ];
    }

    /**
     * @param string|null $attributeToExclude Prevent getting same Attribute from Attributes Array
     *
     * @return string
     */
    private static function randomVariantOptionType(string $attributeToExclude = null): string
    {
        $attributes = [
            'color',
            'size'
        ];

        if ($attributeToExclude !== null) {
            $attributeToBeExcluded = array_search($attributeToExclude, $attributes, true);
            unset($attributes[$attributeToBeExcluded]);
        }

        return Text::randomElement($attributes);
    }

    /**
     * @param string|null $attributeType
     *
     * @return array
     */
    private static function sampleVariantOption(string $attributeType = null): array
    {
        $options = [
            'color' => Text::randomElement([
                'Black',
                'White',
                'Grey',
                'Creme'
            ]),
            'size' => Text::randomElement([
                's',
                'm',
                'l',
                'xl'
            ])
        ];

        if ($attributeType !== null && isset($options[$attributeType])) {
            return [
                'name' => $attributeType,
                'value' => $options[$attributeType],
            ];
        }

        $randomType = Text::randomElement(array_keys($options));

        return [
            'name' => $randomType,
            'value' => $options[$randomType],
        ];
    }
}
