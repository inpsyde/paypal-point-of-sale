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

    public function testAcceptsOnlyPurchaseCreated(): void
    {
        $handler = new PurchaseCreatedHandler(static function (): void {
        }, $this->logger());

        self::assertTrue($handler->accepts($this->payload(EventName::PURCHASE_CREATED)));
        self::assertFalse($handler->accepts($this->payload('InventoryBalanceChanged')));
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

        $handler = new PurchaseCreatedHandler($enqueue, $this->logger());
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

        $handler = new PurchaseCreatedHandler($enqueue, $this->logger());
        $handler->handle($this->payload(EventName::PURCHASE_CREATED, []));

        self::assertFalse($called);
    }
}
