<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider;

use Exception;
use Faker\Provider\ar_SA\Color;
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

    public static function presentation()
    {
        yield [
            self::sampleProductData('presentation'),
        ];
    }

    public static function variant()
    {
        yield [
            self::sampleProductData('variants')[0],
        ];
    }

    public static function variants()
    {
        yield [
            [
                self::sampleProductData('variants')[0],
                self::sampleProductData('variants')[0],
                self::sampleProductData('variants')[0],
            ],
        ];
    }

    public static function imageLookupKey()
    {
        yield [
            self::sampleProductData('imageLookupKeys')[0],
        ];
    }

    public static function imageLookupKeys()
    {
        $firstImageLookupKey = self::sampleProductData(
            'imageLookupKeys'
        )[0];

        $secondImageLookupKey = self::sampleProductData(
            'imageLookupKeys',
            $firstImageLookupKey
        )[0];

        yield [
            [
                $firstImageLookupKey,
                $secondImageLookupKey,
            ],
        ];
    }

    public static function vat()
    {
        yield [self::sampleProductData('vatPercentage')[0]];
    }

    /**
     * @param string|null $segment Optional: provide key to get the item from the sampleData
     * @param string|null $exclude Optional: exclude a Item from some randomized functionality
     *
     * @return array
     *
     * @throws Exception
     */
    public static function sampleProductData(?string $segment = null, ?string $exclude = null): array
    {
        $sampleImageLookupKeys = [
            'x0yH8KnREeequIvGpnO8Qw.jpg',
            'x0yHIKlsXxyquIvGpnO8Qw.jpg',
            'x0kdfGnRGlaquIvGpnO8Qw.jpg',
            'x0000snRf78quIvGpnO8Qw.jpg',
        ];

        if ($segment === 'imageLookupKeys' && $exclude !== null
            && $key = array_search($exclude, $sampleImageLookupKeys, true)
        ) {
            unset($sampleImageLookupKeys[$key]);
        }
        $currency = self::randomCurrency();
        $presentationImageUrls = [
            'https://image.izettle.com/productimage/o/x0yH8KnREeequIvGpnO8Qw.jpg',
            'https://image.izettle.com/productimage/o/zhyL9RExxsuLKsvGpnO8Qw.jpg',
            'https://image.izettle.com/productimage/o/y5ydnfieJHDKBSZsIpnO8Qw.jpg',
        ];

        if ($segment === 'presentation' && $exclude !== null
            && $key = array_search($exclude, $presentationImageUrls, true)
        ) {
            unset($presentationImageUrls[$key]);
        }

        $variantName = Lorem::sentence(4);

        $vat = Text::randomElement([20, 12.5, 5]);

        $sampleData = [
            'uuid' => (string) Uuid::v1(),
            'name' => Lorem::sentence(4),
            'description' => Lorem::sentence(30),
            'imageLookupKeys' => [
                Text::randomElement($sampleImageLookupKeys),
            ],
            'presentation' => [
                'imageUrl' => Text::randomElement($presentationImageUrls),
                'backgroundColor' => Color::hexColor(),
                'textColor' => Color::hexColor(),
            ],
            'variants' => [
                0 => [
                    'uuid' => (string) Uuid::v1(),
                    'name' => $variantName,
                    'sku' => self::createSkuFromProductname($variantName),
                    'barcode' => 'Barcode',
                    'defaultQuantity' => Text::numberBetween(1, 100),
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
            'vatPercentage' => $vat,
            'taxExempt' => false,
        ];

        if ($segment === 'vatPercentage') {
            return [$sampleData[$segment]];
        }

        if ($segment !== null && isset($sampleData[$segment])) {
            return $sampleData[$segment];
        }

        return $sampleData;
    }

    public static function price()
    {
        return [
            'amount' => Text::numberBetween(5, 250),
            'currencyId' => self::randomCurrency(),
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

    private static function randomCurrency(): string
    {
        /**
         * This list of currency code is what the Zettle API reports to be supported
         */
        $codes = [
            'AED',
            'AFA',
            'AFN',
            'ALL',
            'AMD',
            'ANG',
            'AOA',
            'ARS',
            'AUD',
            'AWG',
            'AZM',
            'AZN',
            'BAM',
            'BBD',
            'BDT',
            'BGN',
            'BHD',
            'BIF',
            'BMD',
            'BND',
            'BOB',
            'BRL',
            'BSD',
            'BTN',
            'BWP',
            'BYR',
            'BZD',
            'CAD',
            'CDF',
            'CHF',
            'CLP',
            'CNY',
            'COP',
            'CRC',
            'CSD',
            'CUC',
            'CUP',
            'CVE',
            'CYP',
            'CZK',
            'DJF',
            'DKK',
            'DOP',
            'DZD',
            'EEK',
            'EGP',
            'ERN',
            'ETB',
            'EUR',
            'FJD',
            'FKP',
            'GBP',
            'GEL',
            'GGP',
            'GHC',
            'GHS',
            'GIP',
            'GMD',
            'GNF',
            'GTQ',
            'GYD',
            'HKD',
            'HNL',
            'HRK',
            'HTG',
            'HUF',
            'IDR',
            'ILS',
            'IMP',
            'INR',
            'IQD',
            'IRR',
            'ISK',
            'JEP',
            'JMD',
            'JOD',
            'JPY',
            'KES',
            'KGS',
            'KHR',
            'KMF',
            'KPW',
            'KRW',
            'KWD',
            'KYD',
            'KZT',
            'LAK',
            'LBP',
            'LKR',
            'LRD',
            'LSL',
            'LTL',
            'LVL',
            'LYD',
            'MAD',
            'MDL',
            'MGA',
            'MKD',
            'MMK',
            'MNT',
            'MOP',
            'MRO',
            'MTL',
            'MUR',
            'MVR',
            'MWK',
            'MXN',
            'MYR',
            'MZM',
            'MZN',
            'NAD',
            'NGN',
            'NIO',
            'NOK',
            'NPR',
            'NZD',
            'OMR',
            'PAB',
            'PEN',
            'PGK',
            'PHP',
            'PKR',
            'PLN',
            'PYG',
            'QAR',
            'RON',
            'RSD',
            'RUB',
            'RWF',
            'SAR',
            'SBD',
            'SCR',
            'SDD',
            'SDG',
            'SEK',
            'SGD',
            'SHP',
            'SIT',
            'SKK',
            'SLL',
            'SOS',
            'SPL',
            'SRD',
            'SSP',
            'STD',
            'SVC',
            'SYP',
            'SZL',
            'THB',
            'TJS',
            'TMM',
            'TMT',
            'TND',
            'TOP',
            'TRL',
            'TRY',
            'TTD',
            'TVD',
            'TWD',
            'TZS',
            'UAH',
            'UGX',
            'USD',
            'UYU',
            'UZS',
            'VEB',
            'VEF',
            'VND',
            'VUV',
            'WST',
            'XAF',
            'XAG',
            'XAU',
            'XCD',
            'XDR',
            'XOF',
            'XPD',
            'XPF',
            'XPT',
            'YER',
            'ZAR',
            'ZMK',
            'ZMW',
            'ZWD',
            'ZWL',
        ];

        return $codes[array_rand($codes)];
    }
}
