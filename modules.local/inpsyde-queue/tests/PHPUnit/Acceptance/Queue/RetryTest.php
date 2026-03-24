<?php
declare(strict_types=1);
/**
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 */

namespace Inpsyde\Queue\Tests;

use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RetryTest extends EphemeralQueueTestCase
{

    public function setUp(): void
    {
        $args = $this->getProvidedData();
        $numJobs = $args[0];
        $jobType = $args[1];
        $this->injectFactory(
            'inpsyde.is-multisite',
            function () {
                return false;
            }
        );
        $this->injectFactory(
            'inpsyde.queue.logger',
            $this->single(
                function (ContainerInterface $container) use ($args): LoggerInterface {
                    $maxRetries = (int) $container->get('inpsyde.queue.failed.retry.count');
                    // This instance will run for every job ($args[0]) and once every retry
                    $expectedCalls = ($maxRetries + 1) * $args[0];
                    $logger = Mockery::mock(LoggerInterface::class);
                    $logger->shouldReceive('debug')->times($expectedCalls);
                    $logger->shouldReceive('notice')->times($expectedCalls);
                    $logger->shouldNotReceive('error');

                    return $logger;
                }
            )
        );
        $this->injectFactory(
            "inpsyde.job.{$jobType}",
            $this->single(
                function (ContainerInterface $container) use ($numJobs, $jobType): Job {
                    $maxRetries = (int) $container->get('inpsyde.queue.failed.retry.count');
                    // This instance will run for every job ($args[0]) and once every retry
                    $expectedCalls = ($maxRetries + 1) * $numJobs;
                    $job = Mockery::mock(Job::class);
                    $job->shouldReceive('type')->andReturn($jobType);
                    $job->shouldReceive('execute')->times($expectedCalls)->andReturn(false);

                    return $job;
                }
            )
        );
        parent::setUp();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRetry($jobCount, $jobType)
    {
        $repository = $this->repository();
        $factory = $this->factory();
        for ($i = 0; $i < $jobCount; $i++) {
            $repository->add(
                $factory->fromData(
                    $jobType,
                    Context::fromArray(
                        [],
                        0
                    )
                )
            );
        }

        $processor = $this->processor();
        $processor->process();
        $this->assertTrue(true);
    }

    public function dataProvider()
    {
        yield 'Nena' => [
            99,
            'red-balloons',
        ];

        yield 'Katy Perry' => [
            365,
            'days-of-the-year',
        ];

        yield 'Brewer & Shipley' => [
            1,
            'toke-over-the-line',
        ];
    }

    public function processor(): QueueProcessor
    {
        return $this->get('inpsyde.queue.processor');
    }

    public function repository(): JobRepository
    {
        return $this->get('inpsyde.queue.repository');
    }

    public function factory(): JobRecordFactoryInterface
    {
        return $this->get('inpsyde.queue.factory');
    }
}
