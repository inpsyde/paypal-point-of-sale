<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class VatEntityTest extends BrainMonkeyWpTestCase
{

    public function testCreateEntity(): void
    {
        $percentage = 12.0;
        $vat = new Vat($percentage);

        $this->assertSame((float) $percentage, $vat->percentage());
        $this->assertIsFloat($vat->percentage());
    }
}
