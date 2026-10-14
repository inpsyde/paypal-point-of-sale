<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Inpsyde\Queue\Exception\InvalidJobException;
use Psr\Container\ContainerInterface;

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

        if ($this->container->has($type)) {
            $job = $this->container->get($type);
            if ($job instanceof Job) {
                return new BasicJobRecord($job, $context);
            }
        }

        throw new InvalidJobException("Job type '" . esc_html($type) . "' could not be found");
    }
}
