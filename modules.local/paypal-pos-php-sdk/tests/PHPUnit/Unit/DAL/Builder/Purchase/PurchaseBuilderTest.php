<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Coordinates\CoordinatesBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase\PurchaseBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\User\UserBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates\CoordinatesFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Type\SourceType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Validator\Coordinates\CoordinatesValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\InvalidBuilderPayloadException;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class PurchaseBuilderTest extends BrainMonkeyWpTestCase
{
    private function builder(): PurchaseBuilder
    {
        return new PurchaseBuilder(
            new PurchaseFactory(),
            new UserBuilder(new UserFactory()),
            new CoordinatesBuilder(new CoordinatesFactory(new CoordinatesValidator()))
        );
    }

    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/fixtures/{$name}"), true);
    }

    public function testBuildsSaleFromApiPayload(): void
    {
        $purchase = $this->builder()->buildFromArray($this->fixture('purchase-created.json'));

        self::assertSame('WJwt5j7kEeuecKKsB1sX_Q', $purchase->uuid());
        self::assertInstanceOf(DateTime::class, $purchase->timestamp());
        self::assertSame('2020-12-15 14:46:43', $purchase->timestamp()->format('Y-m-d H:i:s'));
        self::assertSame('SEK', $purchase->currency());
        // Amounts stay in minor units on the entity.
        self::assertSame(70000.0, $purchase->amount());
        self::assertSame(14000.0, $purchase->vatAmount());
        self::assertSame(SourceType::POS, $purchase->sourceType()->getValue());
        self::assertSame('Sara Johansson', $purchase->user()->displayName());
        self::assertCount(1, $purchase->products());
        self::assertCount(1, $purchase->payments());
        self::assertCount(1, $purchase->vatAmounts());
        self::assertFalse($purchase->isRefund());
        self::assertNotNull($purchase->coordinates());
        self::assertEqualsWithDelta(66.609375, (float) $purchase->coordinates()->latitude(), 0.0001);
    }

    public function testBuildsRefund(): void
    {
        $purchase = $this->builder()->buildFromArray($this->fixture('purchase-refund.json'));

        self::assertTrue($purchase->isRefund());
        self::assertSame(-10000.0, $purchase->amount());
        self::assertSame('WJwt5j7kEeuecKKsB1sX_Q', $purchase->refundsPurchaseUuid());
    }

    public function testReadsRefundsPurchaseUuidFromReferences(): void
    {
        $purchase = $this->builder()->buildFromArray([
            'purchaseUUID' => 'r1',
            'timestamp' => '2021-01-01T00:00:00.000+0000',
            'currency' => 'SEK',
            'refund' => true,
            'references' => ['refundsPurchaseUUID' => 'original-uuid'],
        ]);

        self::assertSame('original-uuid', $purchase->refundsPurchaseUuid());
    }

    public function testNonRefundHasNoRefundReference(): void
    {
        $purchase = $this->builder()->buildFromArray($this->fixture('purchase-created.json'));

        self::assertNull($purchase->refundsPurchaseUuid());
    }

    public function testBuildsWithOptionalFieldsMissing(): void
    {
        $minimal = [
            'purchaseUUID' => 'abc',
            'timestamp' => '2021-01-01T00:00:00.000+0000',
            'country' => 'SE',
            'currency' => 'SEK',
            'organizationId' => 1,
            'purchaseNumber' => 2,
            'amount' => 0,
            'vatAmount' => 0,
            'products' => [],
            'payments' => [],
            'vatAmounts' => [],
            'receiptCopyAllowed' => false,
            'refund' => false,
            'refunded' => false,
        ];

        $purchase = $this->builder()->buildFromArray($minimal);

        self::assertSame('abc', $purchase->uuid());
        self::assertNull($purchase->coordinates());
        self::assertNull($purchase->sourceType());
        self::assertSame('', $purchase->user()->displayName());
    }

    /**
     * @dataProvider requiredFields
     */
    public function testRejectsPurchasesWithoutRequiredFields(string $field): void
    {
        $data = $this->fixture('purchase-created.json');
        unset($data[$field]);

        $this->expectException(InvalidBuilderPayloadException::class);
        $this->builder()->buildFromArray($data);
    }

    public function requiredFields(): array
    {
        return [['purchaseUUID'], ['timestamp']];
    }

    public function testRejectsAnUnreadableTimestamp(): void
    {
        $data = $this->fixture('purchase-created.json');
        $data['timestamp'] = 'not a date';

        $this->expectException(InvalidBuilderPayloadException::class);
        $this->builder()->buildFromArray($data);
    }

    public function testKeepsTheDashedPurchaseUuid(): void
    {
        $data = $this->fixture('purchase-created.json');

        self::assertSame($data['purchaseUUID1'], $this->builder()->buildFromArray($data)->uuid1());
    }

    public function testFallsBackToTheShortUuidWithoutADashedOne(): void
    {
        $data = $this->fixture('purchase-created.json');
        unset($data['purchaseUUID1']);

        self::assertSame($data['purchaseUUID'], $this->builder()->buildFromArray($data)->uuid1());
    }

    public function testIgnoresUnknownSource(): void
    {
        $data = $this->fixture('purchase-created.json');
        $data['source'] = 'PAYMENT_LINK';

        self::assertNull($this->builder()->buildFromArray($data)->sourceType());
    }
}
