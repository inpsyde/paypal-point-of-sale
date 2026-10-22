<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Order\PaymentLabels;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\stubTranslationFunctions;
use function Brain\Monkey\Functions\when;

class PaymentLabelsTest extends BrainMonkeyWpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        stubTranslationFunctions();
        when('sanitize_key')->alias(static fn(string $key): string => strtolower($key));
    }

    public function testPrimaryTypeIsHighestAmountTender(): void
    {
        $labels = new PaymentLabels();
        $payments = [
            ['type' => 'IZETTLE_CASH', 'amount' => 4000],
            ['type' => 'GIFTCARD', 'amount' => 6000],
        ];

        self::assertSame('GIFTCARD', $labels->primaryType($payments));
    }

    public function testPrimaryTypeUsesAbsoluteAmountForRefunds(): void
    {
        $labels = new PaymentLabels();
        $payments = [
            ['type' => 'IZETTLE_CARD', 'amount' => -10000],
            ['type' => 'IZETTLE_CASH', 'amount' => -500],
        ];

        self::assertSame('IZETTLE_CARD', $labels->primaryType($payments));
    }

    public function testPrimaryTypeIsEmptyWhenNoPayments(): void
    {
        self::assertSame('', (new PaymentLabels())->primaryType([]));
    }

    public function testKnownTypeGetsReadableTitle(): void
    {
        self::assertSame('PayPal POS Card', (new PaymentLabels())->title('IZETTLE_CARD'));
    }

    public function testUnknownTypeFallsBackToGenericTitle(): void
    {
        self::assertSame('PayPal POS (Other)', (new PaymentLabels())->title('CUSTOM'));
    }

    public function testSlugIsPrefixedAndSanitised(): void
    {
        self::assertSame('paypal_pos_card', (new PaymentLabels())->slug('IZETTLE_CARD'));
    }

    public function testSlugFallsBackWhenTypeIsEmpty(): void
    {
        self::assertSame('paypal_pos', (new PaymentLabels())->slug(''));
    }
}
