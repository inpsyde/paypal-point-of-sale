<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\InvalidJobException;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\BasicJobRecord;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRecord;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\NullJob;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
/**
 * Jobs of unknown types, e.g. of a module that is turned off, become NullJobs:
 * the queue skips them when enqueueing and deletes them like finished jobs.
 */
class DroppingUnknownTypesFactory implements JobRecordFactoryInterface
{
    private JobRecordFactoryInterface $factory;
    private LoggerInterface $logger;
    public function __construct(JobRecordFactoryInterface $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }
    public function fromData(string $type, ContextInterface $context): JobRecord
    {
        try {
            return $this->factory->fromData($type, $context);
        } catch (InvalidJobException $exception) {
            $this->logger->warning(sprintf('Dropping job %d of the unknown type "%s"', $context->id(), $type));
            return new BasicJobRecord(new NullJob(), $context);
        }
    }
}
