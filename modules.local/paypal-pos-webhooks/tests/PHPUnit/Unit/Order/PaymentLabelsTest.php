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

    /**
     * @dataProvider payments
     */
    public function testPrimaryType(array $payments, string $expected): void
    {
        self::assertSame($expected, (new PaymentLabels())->primaryType($payments));
    }

    public function payments(): array
    {
        return [
            'highest amount wins' => [
                [['type' => 'IZETTLE_CASH', 'amount' => 4000], ['type' => 'IZETTLE_CARD', 'amount' => 6000]],
                'IZETTLE_CARD',
            ],
            'refund uses absolute amounts' => [
                [['type' => 'IZETTLE_CARD', 'amount' => -10000], ['type' => 'IZETTLE_CASH', 'amount' => -500]],
                'IZETTLE_CARD',
            ],
            'no payments' => [[], ''],
        ];
    }

    public function testTitle(): void
    {
        $labels = new PaymentLabels();

        self::assertSame('PayPal POS Card', $labels->title('IZETTLE_CARD'));
        self::assertSame('PayPal POS (Other)', $labels->title('SOMETHING_NEW'));
    }

    public function testSlug(): void
    {
        $labels = new PaymentLabels();

        self::assertSame('paypal_pos_card', $labels->slug('IZETTLE_CARD'));
        self::assertSame('paypal_pos_giftcard', $labels->slug('GIFTCARD'));
        self::assertSame('paypal_pos', $labels->slug(''));
    }
}
