<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test\DataProvider;

use Faker\Provider\Color;
use Faker\Provider\Lorem;
use Faker\Provider\Text;
use Symfony\Component\Uid\Uuid;

class ProductSampleData
{

    public static function product()
    {
        yield [
            self::sampleProductData(),
        ];
    }

    public static function products()
    {
        yield [
            [
                self::sampleProductData(),
                self::sampleProductData(),
                self::sampleProductData(),
            ],
        ];
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public static function sampleProductData(): array
    {
        $variantName = Lorem::sentence(4);
        $currency = self::currency();
        $vat = (string) Text::randomElement([20, 12.5, 5]);

        return [
            'uuid' => (string) Uuid::v1(),
            'name' => Lorem::sentence(4),
            'description' => Lorem::sentence(30),
            'imageLookupKeys' => [
                Text::randomElement(
                    [
                        'x0yH8KnREeequIvGpnO8Qw.jpg',
                        'x0yHIKlsXxyquIvGpnO8Qw.jpg',
                        'x0kdfGnRGlaquIvGpnO8Qw.jpg',
                        'x0000snRf78quIvGpnO8Qw.jpg',
                    ]
                ),
            ],
            'presentation' => [
                'imageUrl' => Text::randomElement(
                    [
                        'https://image.izettle.com/productimage/o/x0yH8KnREeequIvGpnO8Qw.jpg',
                        'https://image.izettle.com/productimage/o/zhyL9RExxsuLKsvGpnO8Qw.jpg',
                        'https://image.izettle.com/productimage/o/y5ydnfieJHDKBSZsIpnO8Qw.jpg',
                    ]
                ),
                'backgroundColor' => Color::hexColor(),
                'textColor' => Color::hexColor(),
            ],
            'variants' => [
                0 => [
                    'uuid' => (string) Uuid::v1(),
                    'name' => $variantName,
                    'description' => Lorem::sentence(20),
                    'sku' => self::createSkuFromProductname($variantName),
                    'barcode' => 'Barcode',
                    'defaultQuantity' => (string) Text::numberBetween(1, 100),
                    'price' =>
                        [
                            'amount' => Text::numberBetween(50, 500),
                            'currencyId' => $currency,
                        ],
                    'costPrice' =>
                        [
                            'amount' => Text::numberBetween(5, 250),
                            'currencyId' => $currency,
                        ],
                    'vatPercentage' => $vat,
                ],
            ],
            'externalReference' => 'externalReference',
            'unitName' => 'Kg',
            'vatPercentage' => $vat,
        ];
    }

    /**
     * Helper Function
     *
     * @param string $productName
     *
     * @return string
     */
    private static function createSkuFromProductname(string $productName): string
    {
        $productName = strtolower($productName);
        $productName = preg_replace("/[^a-z0-9_\s-]/", '', $productName);
        $productName = preg_replace("/[\s-]+/", ' ', $productName);
        $productName = preg_replace("/[\s_]/", '_', $productName);

        return $productName;
    }

    private static function currency(): string
    {
        return 'GBP';
    }
}
