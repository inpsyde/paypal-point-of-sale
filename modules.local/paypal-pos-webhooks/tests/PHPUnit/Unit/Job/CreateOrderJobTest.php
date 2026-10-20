<?php

declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;
use Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class CreateOrderJobTest extends BrainMonkeyWpTestCase
{
    private function context(?string $uuid): ContextInterface
    {
        $args = new stdClass();
        if ($uuid !== null) {
            $args->purchaseUuid = $uuid;
        }
        $context = Mockery::mock(ContextInterface::class);
        $context->shouldReceive('args')->andReturn($args);

        return $context;
    }

    private function purchase(bool $refund = false): object
    {
        $user = (new UserFactory())->create('1', 'Cashier');

        return (new PurchaseFactory())->create(
            'abc',
            new DateTime(),
            'SE',
            'SEK',
            $user,
            1,
            1,
            0.0,
            0.0,
            [],
            [],
            [],
            false,
            $refund,
            false
        );
    }

    private function executeJob(CreateOrderJob $job, ?string $uuid, ?LoggerInterface $logger = null): bool
    {
        return $job->execute(
            $this->context($uuid),
            Mockery::mock(JobRepository::class),
            $logger ?? Mockery::mock(LoggerInterface::class)
        );
    }

    public function testSkipsWhenUuidMissing(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldNotReceive('read');
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldNotReceive('createOrder');

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue($this->executeJob($job, null));
    }

    public function testSkipsWhenOrderAlreadyExists(): void
    {
        $webhookUuid = '02076319-cf9f-4222-9cb6-a38d17e7e8e1';
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->with($webhookUuid)->andReturn($this->purchase());
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldReceive('orderExists')->once()->with('abc')->andReturnTrue();
        $factory->shouldNotReceive('createOrder');

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue($this->executeJob($job, $webhookUuid));
    }

    public function testCreatesOrderOnHappyPath(): void
    {
        $purchase = $this->purchase();
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->with('webhook-uuid')->andReturn($purchase);
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldReceive('orderExists')->once()->with('abc')->andReturnFalse();
        $factory->shouldReceive('createOrder')->once()->with($purchase)->andReturn(42);

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue($this->executeJob($job, 'webhook-uuid'));
    }

    public function testSkipsRefundPurchases(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->andReturn($this->purchase(true));
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldNotReceive('orderExists');
        $factory->shouldNotReceive('createOrder');

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue($this->executeJob($job, 'abc'));
    }

    public function testReturnsFalseToRetryOnFailure(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->andThrow(new RuntimeException('boom'));
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldNotReceive('orderExists');
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $job = new CreateOrderJob($purchases, $factory);

        self::assertFalse($this->executeJob($job, 'abc', $logger));
    }
}
