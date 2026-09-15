<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\PayPal\PointOfSale\Webhooks\Handler\PurchaseCreatedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\when;

class PurchaseCreatedHandlerTest extends BrainMonkeyWpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        when('wp_next_scheduled')->justReturn(false);
        when('wp_schedule_single_event')->justReturn(true);
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
        }, 'zettle.queue.cron');

        self::assertTrue($handler->accepts($this->payload(EventName::PURCHASE_CREATED)));
        self::assertFalse($handler->accepts($this->payload('InventoryBalanceChanged')));
    }

    public function testEnqueuesJobWithPurchaseUuid(): void
    {
        $captured = [];
        $enqueue = static function (string $type, array $args) use (&$captured): void {
            $captured = [$type, $args];
        };

        $handler = new PurchaseCreatedHandler($enqueue, 'zettle.queue.cron');
        $handler->handle($this->payload(EventName::PURCHASE_CREATED, ['purchaseUUID' => 'abc']));

        self::assertSame([CreateOrderJob::TYPE, ['purchaseUuid' => 'abc']], $captured);
    }

    public function testDoesNotEnqueueWithoutUuid(): void
    {
        $called = false;
        $enqueue = static function () use (&$called): void {
            $called = true;
        };

        $handler = new PurchaseCreatedHandler($enqueue, 'zettle.queue.cron');
        $handler->handle($this->payload(EventName::PURCHASE_CREATED, []));

        self::assertFalse($called);
    }
}
