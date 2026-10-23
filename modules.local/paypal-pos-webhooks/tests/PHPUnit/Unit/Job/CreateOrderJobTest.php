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
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseRefundFactory;
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

    private function job(
        Purchases $purchases,
        ?PurchaseOrderFactory $orderFactory = null,
        ?PurchaseRefundFactory $refundFactory = null
    ): CreateOrderJob {

        return new CreateOrderJob(
            $purchases,
            $orderFactory ?? Mockery::mock(PurchaseOrderFactory::class),
            $refundFactory ?? Mockery::mock(PurchaseRefundFactory::class)
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
        $orderFactory = Mockery::mock(PurchaseOrderFactory::class);
        $orderFactory->shouldNotReceive('createOrder');

        $job = $this->job($purchases, $orderFactory);

        self::assertTrue($this->executeJob($job, null));
    }

    public function testSkipsWhenOrderAlreadyExists(): void
    {
        $webhookUuid = '02076319-cf9f-4222-9cb6-a38d17e7e8e1';
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->with($webhookUuid)->andReturn($this->purchase());
        $orderFactory = Mockery::mock(PurchaseOrderFactory::class);
        $orderFactory->shouldReceive('orderExists')->once()->with('abc')->andReturnTrue();
        $orderFactory->shouldNotReceive('createOrder');

        $job = $this->job($purchases, $orderFactory);

        self::assertTrue($this->executeJob($job, $webhookUuid));
    }

    public function testCreatesOrderOnHappyPath(): void
    {
        $purchase = $this->purchase();
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->with('webhook-uuid')->andReturn($purchase);
        $orderFactory = Mockery::mock(PurchaseOrderFactory::class);
        $orderFactory->shouldReceive('orderExists')->once()->with('abc')->andReturnFalse();
        $orderFactory->shouldReceive('createOrder')->once()->with($purchase)->andReturn(42);
        $refundFactory = Mockery::mock(PurchaseRefundFactory::class);
        $refundFactory->shouldNotReceive('createRefund');
        $refundFactory->shouldReceive('linkOrphanRefunds')->once()->with('abc', 42);

        $job = $this->job($purchases, $orderFactory, $refundFactory);

        self::assertTrue($this->executeJob($job, 'webhook-uuid'));
    }

    public function testRoutesRefundsToRefundFactory(): void
    {
        $refund = $this->purchase(true);
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->andReturn($refund);
        $orderFactory = Mockery::mock(PurchaseOrderFactory::class);
        $orderFactory->shouldNotReceive('createOrder');
        $refundFactory = Mockery::mock(PurchaseRefundFactory::class);
        $refundFactory->shouldReceive('createRefund')->once()->with($refund);

        $job = $this->job($purchases, $orderFactory, $refundFactory);

        self::assertTrue($this->executeJob($job, 'abc'));
    }

    public function testReturnsFalseToRetryOnFailure(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->andThrow(new RuntimeException('boom'));
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $job = $this->job($purchases);

        self::assertFalse($this->executeJob($job, 'abc', $logger));
    }
}
