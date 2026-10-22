<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class MinorUnitsTest extends BrainMonkeyWpTestCase
{
    public function testScalesTwoDecimalCurrencyByHundred(): void
    {
        self::assertSame(700.0, MinorUnits::toMajor(70000, 'SEK'));
        self::assertSame(-100.0, MinorUnits::toMajor(-10000, 'EUR'));
    }

    public function testZeroDecimalCurrencyIsNotScaled(): void
    {
        self::assertSame(0, MinorUnits::exponent('JPY'));
        self::assertSame(700.0, MinorUnits::toMajor(700, 'JPY'));
    }

    public function testCurrencyCodeIsCaseInsensitive(): void
    {
        self::assertSame(0, MinorUnits::exponent('jpy'));
        self::assertSame(2, MinorUnits::exponent('sek'));
    }

    public function testForintHasTwoDecimals(): void
    {
        self::assertSame(2, MinorUnits::exponent('HUF'));
    }
}
