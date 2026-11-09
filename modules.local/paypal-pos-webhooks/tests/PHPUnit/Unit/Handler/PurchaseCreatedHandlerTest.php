<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\PayPal\PointOfSale\Webhooks\Handler\PurchaseCreatedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;

class PurchaseCreatedHandlerTest extends BrainMonkeyWpTestCase
{
    private function logger(): LoggerInterface
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        return $logger;
    }

    private function payload(string $event, array $data = []): Payload
    {
        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('eventName')->andReturn($event);
        $payload->shouldReceive('payload')->andReturn($data);

        return $payload;
    }

    /**
     * @dataProvider acceptance
     */
    public function testAcceptsOnlyPurchaseCreatedWhenEnabled(bool $enabled, string $event, bool $expected): void
    {
        $handler = new PurchaseCreatedHandler(static function (): void {
        }, $this->logger(), $enabled);

        self::assertSame($expected, $handler->accepts($this->payload($event)));
    }

    public function acceptance(): array
    {
        return [
            'purchase created' => [true, EventName::PURCHASE_CREATED, true],
            'other event' => [true, EventName::INVENTORY_BALANCE_CHANGED, false],
            'order sync disabled' => [false, EventName::PURCHASE_CREATED, false],
        ];
    }

    /**
     * @dataProvider uuidKeys
     */
    public function testEnqueuesJobWithPurchaseUuid(string $key): void
    {
        $captured = [];
        $enqueue = static function (string $type, array $args) use (&$captured): void {
            $captured = [$type, $args];
        };

        $handler = new PurchaseCreatedHandler($enqueue, $this->logger(), true);
        $handler->handle($this->payload(EventName::PURCHASE_CREATED, [$key => 'abc']));

        self::assertSame([CreateOrderJob::TYPE, ['purchaseUuid' => 'abc']], $captured);
    }

    public function uuidKeys(): array
    {
        return [['purchaseUuid'], ['purchaseUUID'], ['uuid']];
    }

    public function testDoesNotEnqueueWithoutUuid(): void
    {
        $called = false;
        $enqueue = static function () use (&$called): void {
            $called = true;
        };

        $handler = new PurchaseCreatedHandler($enqueue, $this->logger(), true);
        $handler->handle($this->payload(EventName::PURCHASE_CREATED, []));

        self::assertFalse($called);
    }
}
