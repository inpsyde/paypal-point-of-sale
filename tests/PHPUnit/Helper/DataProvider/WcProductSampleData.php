<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test\DataProvider;

use Faker\Provider\Text;
use Mockery;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Grouped;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Taxonomy;
use function Brain\Monkey\Functions\expect;

class WcProductSampleData
{

    static $termCache = [];

    /**
     * @param string|null $class
     * @param array $data
     *
     * @return WC_Product
     */
    public static function createWcProduct(int $id, string $class = null, array $data = []): WC_Product
    {
        $classNames = [
            WC_Product_Simple::class,
            WC_Product_Variable::class,
            WC_Product_Variation::class,
            WC_Product_Grouped::class,
        ];

        if (!in_array($class, $classNames, true)) {
            $class = $classNames[array_rand($classNames)];
        }

        return new $class($id, $data);
    }

    /**
     * @param string|null $class
     * @param array $data
     *
     * @return WC_Product
     */
    private static function createWcProductWithAttributes(
        string $class = null,
        array $data = []
    ): WC_Product {
        $classNames = [
            WC_Product_Simple::class,
        ];

        if (!in_array($class, $classNames, true)) {
            $class = $classNames[array_rand($classNames)];
        }

        $attributes = [
            'size' => [
                'small',
                'medium',
                'large',
            ],
            'color' => [
                'red',
                'blue',
                'green',
            ],
        ];
        $attributeName = array_rand($attributes);

        $randomId = Text::numberBetween(50, 60);
        $attribute = self::createProductAttribute($attributeName, $attributes[$attributeName]);
        $attributeType = $attribute->get_name();

        $data['attributes'][$attributeType] = $attribute;

        return new $class($randomId, $data);
    }

    public static function createProductAttribute(
        string $name,
        array $options,
        bool $isTaxonomy = true
    ): WC_Product_Attribute {
        if ($isTaxonomy) {
            $taxSlug = "pa_{$name}";
            expect('get_taxonomy')->with($taxSlug)->andReturnUsing(
                function (): WP_Taxonomy {
                    $args = func_get_args();
                    $taxonomy = Mockery::mock(WP_Taxonomy::class);
                    $taxonomy->label = $args[0];

                    return $taxonomy;
                }
            );
            $options = array_map(
                function (string $option): int {
                    $id = rand(1, PHP_INT_MAX);
                    $term = Mockery::mock(\WP_Term::class);
                    $term->name = $option;
                    $term->slug = $option;
                    self::$termCache[$id] = $term;

                    expect('get_term')->with($id)->andReturnUsing(
                        function (): \WP_Term {
                            $args = func_get_args();

                            return self::$termCache[$args[0]];
                        }
                    );

                    return $id;
                },
                $options
            );
        }

        return new WC_Product_Attribute(
            [
                'name' => $name,
                'options' => $options,
                'taxonomy' => $isTaxonomy
                    ? $taxSlug
                    : '',
                'is_taxonomy' => $isTaxonomy,
            ]
        );
    }

    public static function flush(): void
    {
        self::$termCache = [];
        WC_Product::flush();
    }
}
