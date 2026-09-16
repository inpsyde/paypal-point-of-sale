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

    public function testSkipsWhenUuidMissing(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldNotReceive('read');
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldNotReceive('createOrder');

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue(
            $job->execute($this->context(null), Mockery::mock(JobRepository::class), Mockery::mock(LoggerInterface::class))
        );
    }

    public function testSkipsWhenOrderAlreadyExists(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldNotReceive('read');
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldReceive('orderExists')->once()->with('abc')->andReturnTrue();
        $factory->shouldNotReceive('createOrder');

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue(
            $job->execute($this->context('abc'), Mockery::mock(JobRepository::class), Mockery::mock(LoggerInterface::class))
        );
    }

    public function testCreatesOrderOnHappyPath(): void
    {
        $purchase = $this->purchase();
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->with('abc')->andReturn($purchase);
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldReceive('orderExists')->once()->andReturnFalse();
        $factory->shouldReceive('createOrder')->once()->with($purchase)->andReturn(42);

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue(
            $job->execute($this->context('abc'), Mockery::mock(JobRepository::class), Mockery::mock(LoggerInterface::class))
        );
    }

    public function testSkipsRefundPurchases(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->andReturn($this->purchase(true));
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldReceive('orderExists')->once()->andReturnFalse();
        $factory->shouldNotReceive('createOrder');

        $job = new CreateOrderJob($purchases, $factory);

        self::assertTrue(
            $job->execute($this->context('abc'), Mockery::mock(JobRepository::class), Mockery::mock(LoggerInterface::class))
        );
    }

    public function testReturnsFalseToRetryOnFailure(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('read')->once()->andThrow(new RuntimeException('boom'));
        $factory = Mockery::mock(PurchaseOrderFactory::class);
        $factory->shouldReceive('orderExists')->once()->andReturnFalse();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $job = new CreateOrderJob($purchases, $factory);

        self::assertFalse(
            $job->execute($this->context('abc'), Mockery::mock(JobRepository::class), $logger)
        );
    }
}
