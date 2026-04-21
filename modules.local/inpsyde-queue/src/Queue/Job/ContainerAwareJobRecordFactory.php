<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Inpsyde\Queue\Exception\InvalidJobException;
use Psr\Container\ContainerInterface;

// phpcs:disable Generic.PHP.DiscourageGoto

class ContainerAwareJobRecordFactory implements JobRecordFactoryInterface
{
    private ContainerInterface $container;

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
        throw new InvalidJobException("Job type '" . esc_html($type) . "' could not be found");
    }
}
