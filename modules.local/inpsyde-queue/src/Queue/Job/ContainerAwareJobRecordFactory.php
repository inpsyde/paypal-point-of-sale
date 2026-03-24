<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Inpsyde\Queue\Exception\InvalidJobException;
use Psr\Container\ContainerInterface;

/**
 * Class QueueJobFactory
 *
 * @package Inpsyde\Queue\Queue
 */
class ContainerAwareJobRecordFactory implements JobRecordFactoryInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * QueueEntryFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function fromData(
        string $type,
        ContextInterface $context
    ): JobRecord {

        if (!$this->container->has($type)) {
            goto error;
        }
        $job = $this->container->get($type);
        if (!($job instanceof Job)) {
            goto error;
        }

        return new BasicJobRecord($job, $context);
        // phpcs:disable Squiz.PHP.NonExecutableCode
        error:
        throw new InvalidJobException("Job type '{$type}' could not be found");
    }
}
