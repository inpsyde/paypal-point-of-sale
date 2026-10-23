<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;
use Syde\PayPal\PointOfSale\Webhooks\Order\PaymentLabels;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseFilter;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class PurchaseFilterTest extends BrainMonkeyWpTestCase
{
    private function paidWith(array $payments): Purchase
    {
        return (new PurchaseFactory())->create(
            'uuid',
            new DateTime(),
            'GB',
            'GBP',
            (new UserFactory())->create('1', 'Cashier'),
            1,
            1,
            0.0,
            0.0,
            [],
            $payments,
            [],
            false,
            false,
            false
        );
    }

    public function testNoSelectionSyncsEverything(): void
    {
        self::assertTrue((new PurchaseFilter(new PaymentLabels(), []))->allows($this->paidWith([])));
    }

    public function testSyncsByTheTenderTheOrderIsLabelledWith(): void
    {
        $filter = new PurchaseFilter(new PaymentLabels(), ['IZETTLE_CARD']);
        $split = [['type' => 'IZETTLE_CARD', 'amount' => 600], ['type' => 'IZETTLE_CASH', 'amount' => 400]];

        self::assertTrue($filter->allows($this->paidWith($split)));
        self::assertFalse($filter->allows($this->paidWith([['type' => 'IZETTLE_CASH', 'amount' => 840]])));
    }
}
