<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class PriceEntityTest extends BrainMonkeyWpTestCase
{

    public function testCreateEntity(): void
    {
        $amountFloat = 12.34;
        $amount =  (int) round($amountFloat * 100);
        $currencyId = 'ABC';
        $price = new Price($amount, $currencyId);

        $this->assertSame($amount, $price->amount());
        $this->assertSame($amountFloat, $price->amountToFloat());
        $this->assertSame($currencyId, $price->currencyId());
        $this->assertIsString($price->currencyId());
    }
}
