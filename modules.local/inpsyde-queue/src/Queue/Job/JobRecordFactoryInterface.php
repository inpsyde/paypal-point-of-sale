<?php

namespace Inpsyde\Queue\Queue\Job;

use DateTime;
use Inpsyde\Queue\Exception\InvalidJobException;
use stdClass;

interface JobRecordFactoryInterface
{

    /**
     * @param string $class
     * @param ContextInterface $context
     * @throws InvalidJobException
     * @return JobRecord
     */
    public function fromData(string $class, ContextInterface $context): JobRecord;
}
