<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit\Queue;

use Inpsyde\Queue\Exception\InvalidJobException;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\NullJob;
use Mockery;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\Queue\DroppingUnknownTypesFactory;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

class DroppingUnknownTypesFactoryTest extends MonkeryTestCase
{
    public function testUnknownTypeBecomesANullJobWithAWarning(): void
    {
        $context = Context::fromArray([], 1, 0, null, 7);
        $factory = Mockery::mock(JobRecordFactoryInterface::class);
        $factory->shouldReceive('fromData')->andThrow(new InvalidJobException('not found'));
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once()->with(Mockery::pattern('/job 7 .*"webhook-purchase-created"/'));

        $record = (new DroppingUnknownTypesFactory($factory, $logger))->fromData('webhook-purchase-created', $context);

        $this->assertInstanceOf(NullJob::class, $record->job());
        $this->assertSame($context, $record->context());
    }
}
