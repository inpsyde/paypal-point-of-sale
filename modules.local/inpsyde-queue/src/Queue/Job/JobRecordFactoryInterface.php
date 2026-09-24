<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Inpsyde\Queue\Exception\InvalidJobException;

interface JobRecordFactoryInterface
{
    /**
     * @param string $type
     * @param ContextInterface $context
     * @throws InvalidJobException
     * @return JobRecord
     */
    public function fromData(string $type, ContextInterface $context): JobRecord;
}
